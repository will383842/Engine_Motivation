<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterSequence;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\AbTest;
use App\Models\AbTestVariant;
use App\Models\ChatterAbAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SequenceEngine
{
    public function __construct(
        private MotivationDispatcher $dispatcher,
        private SegmentResolver $segmentResolver,
    ) {}

    public function enroll(Chatter $chatter, Sequence $sequence): ?ChatterSequence
    {
        if ($sequence->status !== 'active') {
            return null;
        }

        // Check max concurrent sequences per chatter
        $activeCount = ChatterSequence::where('chatter_id', $chatter->id)
            ->where('status', 'active')
            ->count();

        if ($activeCount >= ($sequence->max_concurrent ?? 3)) {
            return null;
        }

        // Don't re-enroll if already active in this sequence (unless repeatable)
        if (!$sequence->is_repeatable) {
            $existing = ChatterSequence::where('chatter_id', $chatter->id)
                ->where('sequence_id', $sequence->id)
                ->where('status', 'active')
                ->exists();
            if ($existing) {
                return null;
            }
        }

        $firstStep = $sequence->steps()->orderBy('step_order')->first();

        return ChatterSequence::create([
            'chatter_id' => $chatter->id,
            'sequence_id' => $sequence->id,
            'sequence_version' => $sequence->version,
            'current_step_id' => $firstStep?->id,
            'current_step_order' => 0,
            'status' => 'active',
            'next_step_at' => $firstStep?->delay_seconds
                ? now()->addSeconds($firstStep->delay_seconds)
                : now(),
        ]);
    }

    public function advanceAll(): int
    {
        $processed = 0;

        ChatterSequence::where('status', 'active')
            ->where('next_step_at', '<=', now())
            ->with(['chatter', 'sequence', 'currentStep'])
            ->join('sequences', 'chatter_sequences.sequence_id', '=', 'sequences.id')
            ->orderByDesc('sequences.priority')
            ->select('chatter_sequences.*')
            ->chunkById(100, function ($enrollments) use (&$processed) {
                foreach ($enrollments as $enrollment) {
                    // Lock to prevent duplicate processing
                    $lockKey = "seqlock:{$enrollment->id}:{$enrollment->current_step_id}";
                    if (!Redis::set($lockKey, '1', 'EX', 300, 'NX')) {
                        continue;
                    }

                    try {
                        $this->processStep($enrollment);
                        $processed++;
                    } finally {
                        Redis::del($lockKey);
                    }
                }
            });

        return $processed;
    }

    public function processStep(ChatterSequence $enrollment): void
    {
        $step = $enrollment->currentStep;
        if (!$step) {
            $enrollment->update(['status' => 'completed', 'completed_at' => now()]);
            return;
        }

        // Check version mismatch — auto-migrate if sequence was updated
        if ($enrollment->sequence_version < $enrollment->sequence->version) {
            $this->migrateToNewVersion($enrollment->sequence);
            $enrollment->refresh();
            $step = $enrollment->currentStep;
            if (!$step) {
                $enrollment->update(['status' => 'completed', 'completed_at' => now()]);
                return;
            }
        }

        // Check exit conditions
        if ($this->shouldExit($enrollment)) {
            $enrollment->update(['status' => 'exited', 'exit_reason' => 'exit_condition_met']);
            return;
        }

        try {
            match ($step->type) {
                'message' => $this->processMessageStep($enrollment, $step),
                'delay' => $this->processDelayStep($enrollment, $step),
                'condition' => $this->processConditionStep($enrollment, $step),
                'ab_split' => $this->processAbSplitStep($enrollment, $step),
                'action' => $this->processActionStep($enrollment, $step),
                'webhook' => $this->processWebhookStep($enrollment, $step),
                default => $this->advanceToNext($enrollment),
            };
        } catch (\Throwable $e) {
            Log::error("Sequence step failed: {$e->getMessage()}", [
                'enrollment_id' => $enrollment->id,
                'step_id' => $step->id,
            ]);
        }
    }

    private function processMessageStep(ChatterSequence $enrollment, SequenceStep $step): void
    {
        $chatter = $enrollment->chatter;
        $channel = $step->channel ?? $this->dispatcher->resolveChannel($chatter);

        $this->dispatcher->send($chatter, $step->template_id, $channel);
        $this->advanceToNext($enrollment);
    }

    private function processDelayStep(ChatterSequence $enrollment, SequenceStep $step): void
    {
        $delaySeconds = $step->delay_seconds ?? 86400;
        $this->advanceToNext($enrollment, $delaySeconds);
    }

    private function processConditionStep(ChatterSequence $enrollment, SequenceStep $step): void
    {
        $rules = $step->condition_rules ?? [];
        $chatter = $enrollment->chatter;

        $passed = $this->evaluateConditions($chatter, $rules);
        if (!$passed) {
            $exitOnFail = $rules['exit_on_fail'] ?? false;
            if ($exitOnFail) {
                $enrollment->update(['status' => 'exited', 'exit_reason' => 'condition_failed']);
                return;
            }
            // Jump to specific step if defined
            $jumpTo = $rules['jump_to_step'] ?? null;
            if ($jumpTo) {
                $jumpStep = SequenceStep::where('sequence_id', $enrollment->sequence_id)
                    ->where('step_order', $jumpTo)
                    ->first();
                if ($jumpStep) {
                    $enrollment->update([
                        'current_step_id' => $jumpStep->id,
                        'current_step_order' => $jumpStep->step_order,
                        'next_step_at' => now(),
                    ]);
                    return;
                }
            }
        }
        $this->advanceToNext($enrollment);
    }

    /**
     * A/B split step: assign chatter to a variant and route accordingly.
     */
    private function processAbSplitStep(ChatterSequence $enrollment, SequenceStep $step): void
    {
        $metadata = $step->metadata ?? [];
        $abTestId = $metadata['ab_test_id'] ?? $step->ab_test_id ?? null;

        if (!$abTestId) {
            $this->advanceToNext($enrollment);
            return;
        }

        $abTest = AbTest::find($abTestId);
        if (!$abTest || $abTest->status !== 'running') {
            $this->advanceToNext($enrollment);
            return;
        }

        $chatter = $enrollment->chatter;

        // Check existing assignment
        $assignment = ChatterAbAssignment::where('chatter_id', $chatter->id)
            ->where('ab_test_id', $abTestId)
            ->first();

        if (!$assignment) {
            // Assign to variant based on traffic split
            $variants = $abTest->variants;
            $trafficSplit = $abTest->traffic_split ?? [];
            $rand = mt_rand(1, 100);
            $cumulative = 0;
            $selectedVariant = $variants->first();

            foreach ($variants as $variant) {
                $weight = $trafficSplit[$variant->name] ?? (int) (100 / max($variants->count(), 1));
                $cumulative += $weight;
                if ($rand <= $cumulative) {
                    $selectedVariant = $variant;
                    break;
                }
            }

            $assignment = ChatterAbAssignment::create([
                'chatter_id' => $chatter->id,
                'ab_test_id' => $abTestId,
                'variant_id' => $selectedVariant->id,
            ]);

            $selectedVariant->increment('sent_count');
        }

        // Route to the variant's template
        $variant = AbTestVariant::find($assignment->variant_id);
        if ($variant && $variant->template_id) {
            $channel = $this->dispatcher->resolveChannel($chatter);
            $this->dispatcher->send($chatter, $variant->template_id, $channel);
        }

        $this->advanceToNext($enrollment);
    }

    private function processActionStep(ChatterSequence $enrollment, SequenceStep $step): void
    {
        $metadata = $step->metadata ?? [];
        $action = $metadata['action'] ?? null;

        match ($action) {
            'add_tag' => $enrollment->chatter->update([
                'extra' => array_merge($enrollment->chatter->extra ?? [], ['tags' => array_merge($enrollment->chatter->extra['tags'] ?? [], [$metadata['tag']])]),
            ]),
            'award_xp' => $enrollment->chatter->increment('total_xp', $metadata['xp'] ?? 0),
            default => null,
        };

        $this->advanceToNext($enrollment);
    }

    private function processWebhookStep(ChatterSequence $enrollment, SequenceStep $step): void
    {
        // Webhook steps are processed asynchronously — just advance
        $this->advanceToNext($enrollment);
    }

    /**
     * Migrate active enrollments to a new sequence version.
     * Chatters on steps that still exist continue; others restart from beginning.
     */
    public function migrateToNewVersion(Sequence $sequence): int
    {
        $migrated = 0;

        $activeEnrollments = ChatterSequence::where('sequence_id', $sequence->id)
            ->where('status', 'active')
            ->where('sequence_version', '<', $sequence->version)
            ->get();

        foreach ($activeEnrollments as $enrollment) {
            $currentStepOrder = $enrollment->current_step_order;

            // Find matching step in new version
            $matchingStep = $sequence->steps()
                ->where('step_order', $currentStepOrder)
                ->first();

            if ($matchingStep) {
                // Step still exists at same position — continue from there
                $enrollment->update([
                    'sequence_version' => $sequence->version,
                    'current_step_id' => $matchingStep->id,
                ]);
            } else {
                // Step removed — restart from beginning of new version
                $firstStep = $sequence->steps()->orderBy('step_order')->first();
                $enrollment->update([
                    'sequence_version' => $sequence->version,
                    'current_step_id' => $firstStep?->id,
                    'current_step_order' => 0,
                    'next_step_at' => now(),
                ]);
            }

            $migrated++;
        }

        Log::info("Migrated {$migrated} enrollments to sequence {$sequence->name} v{$sequence->version}");
        return $migrated;
    }

    private function advanceToNext(ChatterSequence $enrollment, int $delaySeconds = 0): void
    {
        $nextStep = SequenceStep::where('sequence_id', $enrollment->sequence_id)
            ->where('step_order', '>', $enrollment->current_step_order)
            ->orderBy('step_order')
            ->first();

        if (!$nextStep) {
            $enrollment->update(['status' => 'completed', 'completed_at' => now()]);
            return;
        }

        $nextAt = $delaySeconds > 0
            ? now()->addSeconds($delaySeconds)
            : ($nextStep->delay_seconds ? now()->addSeconds($nextStep->delay_seconds) : now());

        $enrollment->update([
            'current_step_id' => $nextStep->id,
            'current_step_order' => $nextStep->step_order,
            'next_step_at' => $nextAt,
        ]);
    }

    private function shouldExit(ChatterSequence $enrollment): bool
    {
        $exitConditions = $enrollment->sequence->exit_conditions ?? [];
        $chatter = $enrollment->chatter;

        foreach ($exitConditions as $condition) {
            $type = $condition['type'] ?? '';
            $passed = match ($type) {
                'days_elapsed' => $enrollment->created_at->diffInDays(now()) >= ($condition['value'] ?? 14),
                'opt_out' => !$chatter->is_active,
                'status_suspended' => $chatter->lifecycle_state === 'sunset',
                'has_sale' => ($chatter->total_sales ?? 0) > 0 && ($condition['exit_if_sale'] ?? false),
                default => false,
            };
            if ($passed) {
                return true;
            }
        }

        return false;
    }

    private function evaluateConditions(Chatter $chatter, array $rules): bool
    {
        foreach ($rules['conditions'] ?? [] as $condition) {
            $field = $condition['field'] ?? '';
            $op = $condition['operator'] ?? 'eq';
            $value = $condition['value'] ?? null;

            $actual = data_get($chatter, $field);
            $passed = match ($op) {
                'eq' => $actual == $value,
                'neq' => $actual != $value,
                'gt' => $actual > $value,
                'gte' => $actual >= $value,
                'lt' => $actual < $value,
                'lte' => $actual <= $value,
                'in' => is_array($value) && in_array($actual, $value),
                'not_in' => is_array($value) && !in_array($actual, $value),
                'is_null' => is_null($actual),
                'is_not_null' => !is_null($actual),
                default => true,
            };
            if (!$passed) {
                return false;
            }
        }
        return true;
    }
}
