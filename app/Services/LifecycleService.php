<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterLifecycleTransition;
use App\Events\LifecycleTransition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LifecycleService
{
    public function transition(Chatter $chatter, string $toState, string $reason, string $triggeredBy = 'cron'): void
    {
        $fromState = $chatter->lifecycle_state;
        if ($fromState === $toState) {
            return;
        }

        DB::transaction(function () use ($chatter, $fromState, $toState, $reason, $triggeredBy) {
            $chatter->update([
                'lifecycle_state' => $toState,
                'lifecycle_changed_at' => now(),
            ]);

            ChatterLifecycleTransition::create([
                'chatter_id' => $chatter->id,
                'from_state' => $fromState,
                'to_state' => $toState,
                'reason' => $reason,
                'triggered_by' => $triggeredBy,
            ]);

            event(new LifecycleTransition($chatter, $fromState, $toState));
        });
    }

    /**
     * Reverse transition: reactivate a declining/dormant/churned chatter on new commission.
     */
    public function reactivate(Chatter $chatter, string $reason = 'new_commission'): void
    {
        if (in_array($chatter->lifecycle_state, ['declining', 'dormant', 'churned'])) {
            $this->transition($chatter, 'active', $reason, 'event');
        }
    }

    public function processAll(): int
    {
        $count = 0;

        // ONBOARDING -> ACTIVE (14 days or first commission)
        Chatter::where('lifecycle_state', 'onboarding')
            ->where('created_at', '<', now()->subDays(14))
            ->chunkById(100, function ($chatters) use (&$count) {
                foreach ($chatters as $chatter) {
                    $this->transition($chatter, 'active', 'onboarding_14d_complete');
                    $count++;
                }
            });

        // ACTIVE -> DECLINING (14 days no activity)
        Chatter::where('lifecycle_state', 'active')
            ->where('last_active_at', '<', now()->subDays(14))
            ->chunkById(100, function ($chatters) use (&$count) {
                foreach ($chatters as $chatter) {
                    $this->transition($chatter, 'declining', 'inactivity_14d');
                    $count++;
                }
            });

        // DECLINING -> DORMANT (30 days total)
        Chatter::where('lifecycle_state', 'declining')
            ->where('last_active_at', '<', now()->subDays(30))
            ->chunkById(100, function ($chatters) use (&$count) {
                foreach ($chatters as $chatter) {
                    $this->transition($chatter, 'dormant', 'inactivity_30d');
                    $count++;
                }
            });

        // DORMANT -> CHURNED (60 days)
        Chatter::where('lifecycle_state', 'dormant')
            ->where('last_active_at', '<', now()->subDays(60))
            ->chunkById(100, function ($chatters) use (&$count) {
                foreach ($chatters as $chatter) {
                    $this->transition($chatter, 'churned', 'inactivity_60d');
                    $count++;
                }
            });

        // CHURNED -> SUNSET (90 days + fatigue > 80)
        Chatter::where('lifecycle_state', 'churned')
            ->where('last_active_at', '<', now()->subDays(90))
            ->chunkById(100, function ($chatters) use (&$count) {
                foreach ($chatters as $chatter) {
                    $fatigue = $chatter->fatigueScores()->where('channel', 'telegram')->first();
                    if (!$fatigue || $fatigue->fatigue_score >= 80) {
                        $this->transition($chatter, 'sunset', 'sunset_policy');
                        $count++;
                    }
                }
            });

        return $count;
    }
}
