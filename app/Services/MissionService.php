<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterMission;
use App\Models\Mission;
use App\Models\RewardsLedger;
use App\Events\MissionCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MissionService
{
    private const DAILY_MISSION_SLOTS = 3;
    private const SWEEP_BONUS_XP = 50;

    /**
     * Assign all onboarding missions to a newly registered chatter.
     */
    public function assignOnboardingMissions(Chatter $chatter): void
    {
        $onboardingMissions = Mission::where('type', 'one_time')
            ->where('status', 'active')
            ->whereNotNull('available_from')
            ->orWhere(function ($q) {
                $q->where('type', 'one_time')->where('status', 'active');
            })
            ->orderBy('sort_order')
            ->get();

        // Fallback: if no specific "onboarding" filter, get all one_time missions
        if ($onboardingMissions->isEmpty()) {
            $onboardingMissions = Mission::where('type', 'one_time')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get();
        }

        foreach ($onboardingMissions as $mission) {
            ChatterMission::firstOrCreate(
                ['chatter_id' => $chatter->id, 'mission_id' => $mission->id],
                [
                    'status' => 'assigned',
                    'target_count' => $mission->target_count,
                ],
            );
        }
    }

    /**
     * Assign exactly 3 daily missions via rotation.
     * Picks missions the chatter hasn't done recently, cycling through the pool.
     */
    public function assignDailyMissions(Chatter $chatter): void
    {
        $allDailyMissions = Mission::where('type', 'daily')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        if ($allDailyMissions->isEmpty()) {
            return;
        }

        // Get IDs of missions assigned in the last 3 days (for rotation)
        $recentMissionIds = ChatterMission::where('chatter_id', $chatter->id)
            ->whereHas('mission', fn ($q) => $q->where('type', 'daily'))
            ->where('created_at', '>=', now()->subDays(3))
            ->pluck('mission_id')
            ->toArray();

        // Prioritize unrecent missions, then fall back to any
        $available = $allDailyMissions->reject(fn ($m) => in_array($m->id, $recentMissionIds));
        if ($available->count() < self::DAILY_MISSION_SLOTS) {
            $available = $allDailyMissions;
        }

        // Deterministic-random selection seeded by chatter+date for consistency
        $seed = crc32($chatter->id . now()->toDateString());
        $shuffled = $available->shuffle($seed);
        $selected = $shuffled->take(self::DAILY_MISSION_SLOTS);

        foreach ($selected as $mission) {
            ChatterMission::firstOrCreate(
                [
                    'chatter_id' => $chatter->id,
                    'mission_id' => $mission->id,
                    'status' => 'assigned',
                ],
                [
                    'target_count' => $mission->target_count,
                    'expires_at' => now()->endOfDay(),
                ]
            );
        }
    }

    public function incrementProgress(Chatter $chatter, string $eventType, int $amount = 1): void
    {
        $activeMissions = ChatterMission::where('chatter_id', $chatter->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->with('mission')
            ->get();

        foreach ($activeMissions as $chatterMission) {
            $criteria = $chatterMission->mission->criteria ?? [];
            if (!$this->matchesCriteria($eventType, $criteria)) {
                continue;
            }

            DB::transaction(function () use ($chatterMission, $chatter, $amount) {
                $chatterMission->progress_count += $amount;
                if ($chatterMission->status === 'assigned') {
                    $chatterMission->status = 'in_progress';
                }

                if ($chatterMission->progress_count >= $chatterMission->target_count) {
                    $chatterMission->status = 'completed';
                    $chatterMission->completed_at = now();
                    $chatterMission->reward_granted = true;
                    $chatterMission->save();

                    $xp = $chatterMission->mission->xp_reward;
                    event(new MissionCompleted($chatter, $chatterMission->mission, $xp));

                    // Check sweep bonus after each mission completion
                    $this->checkSweepBonus($chatter);
                } else {
                    $chatterMission->save();
                }
            });
        }
    }

    /**
     * Sweep bonus: +50 XP when all 3 daily missions are completed today.
     */
    private function checkSweepBonus(Chatter $chatter): void
    {
        $today = now()->toDateString();
        $sweepKey = "mission:sweep:{$chatter->id}:{$today}";

        // Already awarded today
        if (Redis::get($sweepKey)) {
            return;
        }

        $todayCompleted = ChatterMission::where('chatter_id', $chatter->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->whereHas('mission', fn ($q) => $q->where('type', 'daily'))
            ->count();

        if ($todayCompleted >= self::DAILY_MISSION_SLOTS) {
            $chatter->increment('total_xp', self::SWEEP_BONUS_XP);

            RewardsLedger::create([
                'chatter_id' => $chatter->id,
                'reward_type' => 'sweep_bonus',
                'amount' => self::SWEEP_BONUS_XP,
                'source' => 'mission',
                'description' => "Sweep bonus: all {$todayCompleted} daily missions completed",
            ]);

            Redis::setex($sweepKey, 172800, '1'); // 48h TTL

            Log::info("Sweep bonus awarded to chatter {$chatter->id}: +" . self::SWEEP_BONUS_XP . " XP");
        }
    }

    private function matchesCriteria(string $eventType, array $criteria): bool
    {
        $triggerEvent = $criteria['trigger_event'] ?? $criteria['event_type'] ?? null;
        if (!$triggerEvent) {
            return true;
        }
        return $triggerEvent === $eventType;
    }
}
