<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\Streak;
use App\Models\RewardsLedger;
use App\Events\StreakBroken;
use App\Events\StreakMilestone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class StreakService
{
    private const MILESTONES = [3, 7, 14, 30, 60, 90, 180, 365];

    /** XP multipliers by streak tier */
    private const MULTIPLIERS = [
        6   => 1.00,  // 1-6 days
        13  => 1.05,  // 7-13 days
        29  => 1.10,  // 14-29 days
        99  => 1.20,  // 30-99 days
        PHP_INT_MAX => 1.50,  // 100+ days
    ];

    private const FREEZE_COST_CENTS = 200;      // $2
    private const FREEZE_MAX_RESERVE = 3;
    private const FREEZE_MAX_PER_WEEK = 2;
    private const RESCUE_COST_CENTS = 500;       // $5
    private const RESCUE_WINDOW_HOURS = 48;

    /** Free freeze milestones */
    private const FREE_FREEZE_MILESTONES = [7, 14, 30];

    public function getMultiplier(int $streakDays): float
    {
        foreach (self::MULTIPLIERS as $maxDay => $multiplier) {
            if ($streakDays <= $maxDay) {
                return $multiplier;
            }
        }
        return 1.50;
    }

    public function recordActivity(Chatter $chatter): void
    {
        $streak = $chatter->streak ?? Streak::create(['chatter_id' => $chatter->id]);

        $chatterTz = $chatter->timezone ?: 'UTC';
        $today = Carbon::now($chatterTz)->toDateString();

        // Already recorded today
        if ($streak->last_activity_date === $today) {
            return;
        }

        $yesterday = Carbon::yesterday($chatterTz)->toDateString();

        DB::transaction(function () use ($streak, $chatter, $today, $yesterday) {
            if ($streak->last_activity_date === $yesterday || $streak->current_count === 0) {
                $streak->current_count++;
                $streak->last_activity_date = $today;
                if ($streak->current_count === 1) {
                    $streak->started_at = now();
                }
                if ($streak->current_count > $streak->longest_count) {
                    $streak->longest_count = $streak->current_count;
                }
                $streak->save();

                $chatter->update([
                    'current_streak' => $streak->current_count,
                    'longest_streak' => $streak->longest_count,
                ]);

                // Award free freeze at milestones
                if (in_array($streak->current_count, self::FREE_FREEZE_MILESTONES)) {
                    $newMax = min($streak->freeze_count_max + 1, self::FREEZE_MAX_RESERVE);
                    $streak->update(['freeze_count_max' => $newMax]);
                }

                if (in_array($streak->current_count, self::MILESTONES)) {
                    event(new StreakMilestone($chatter, $streak->current_count));
                }
            } else {
                // Streak mercy: if conversion happened on break day, restore
                // (caller already validated it's a valid activity like sale/click/referral)
                if ($streak->broken_at && Carbon::parse($streak->broken_at)->isToday()) {
                    // Mercy — restore streak for free
                    $streak->update([
                        'current_count' => $streak->current_count + 1,
                        'last_activity_date' => $today,
                        'broken_at' => null,
                    ]);
                    $chatter->update(['current_streak' => $streak->current_count]);
                    return;
                }

                $this->breakStreak($chatter, $streak);
                $streak->update([
                    'current_count' => 1,
                    'last_activity_date' => $today,
                    'started_at' => now(),
                ]);
                $chatter->update(['current_streak' => 1]);
            }
        });

        Redis::setex("streak:today:{$chatter->id}:{$today}", 172800, '1');
    }

    public function checkAndBreakInactive(Chatter $chatter): void
    {
        $streak = $chatter->streak;
        if (!$streak || $streak->current_count === 0) {
            return;
        }

        $chatterTz = $chatter->timezone ?: 'UTC';
        $today = Carbon::now($chatterTz)->toDateString();
        $yesterday = Carbon::yesterday($chatterTz)->toDateString();

        // Streak is frozen — skip
        if ($streak->streak_frozen_until && $streak->streak_frozen_until >= $today) {
            return;
        }

        // Auto-use freeze from reserve if available
        if ($streak->last_activity_date < $yesterday && $streak->freeze_count_used < $streak->freeze_count_max) {
            $this->freezeStreak($chatter);
            return;
        }

        if ($streak->last_activity_date < $yesterday) {
            $this->breakStreak($chatter, $streak);
        }
    }

    public function freezeStreak(Chatter $chatter): bool
    {
        $streak = $chatter->streak;
        if (!$streak || $streak->freeze_count_used >= ($streak->freeze_count_max ?? self::FREEZE_MAX_RESERVE)) {
            return false;
        }

        // Check weekly purchase limit
        $freezesThisWeek = (int) Redis::get("streak:freeze_week:{$chatter->id}") ?: 0;
        if ($freezesThisWeek >= self::FREEZE_MAX_PER_WEEK) {
            return false;
        }

        $streak->update([
            'streak_frozen_until' => Carbon::now($chatter->timezone ?: 'UTC')->addDay()->toDateString(),
            'freeze_count_used' => $streak->freeze_count_used + 1,
        ]);

        Redis::incr("streak:freeze_week:{$chatter->id}");
        Redis::expire("streak:freeze_week:{$chatter->id}", 604800); // 7 days

        return true;
    }

    /**
     * Purchase a streak freeze ($2, deducted from balance).
     */
    public function purchaseFreeze(Chatter $chatter): bool
    {
        if ($chatter->balance_cents < self::FREEZE_COST_CENTS) {
            return false;
        }

        $streak = $chatter->streak;
        if (!$streak || ($streak->freeze_count_max ?? 0) >= self::FREEZE_MAX_RESERVE) {
            return false;
        }

        $chatter->decrement('balance_cents', self::FREEZE_COST_CENTS);
        $streak->update(['freeze_count_max' => ($streak->freeze_count_max ?? 0) + 1]);

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'streak_freeze',
            'amount' => -self::FREEZE_COST_CENTS,
            'source' => 'manual',
            'description' => 'Streak freeze purchased',
        ]);

        return true;
    }

    /**
     * Streak rescue: $5, available within 48h after break.
     */
    public function rescueStreak(Chatter $chatter): bool
    {
        $streak = $chatter->streak;
        if (!$streak || !$streak->broken_at) {
            return false;
        }

        // Must be within 48h of break
        if (Carbon::parse($streak->broken_at)->diffInHours(now()) > self::RESCUE_WINDOW_HOURS) {
            return false;
        }

        if ($chatter->balance_cents < self::RESCUE_COST_CENTS) {
            return false;
        }

        $chatter->decrement('balance_cents', self::RESCUE_COST_CENTS);

        // Restore the previous streak
        $previousCount = $streak->previous_count ?? $streak->longest_count;
        $streak->update([
            'current_count' => $previousCount,
            'broken_at' => null,
            'last_activity_date' => Carbon::now($chatter->timezone ?: 'UTC')->toDateString(),
        ]);
        $chatter->update(['current_streak' => $previousCount]);

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'custom',
            'amount' => -self::RESCUE_COST_CENTS,
            'source' => 'manual',
            'description' => 'Streak rescue purchased',
        ]);

        return true;
    }

    private function breakStreak(Chatter $chatter, Streak $streak): void
    {
        $previousStreak = $streak->current_count;
        $streak->update([
            'current_count' => 0,
            'broken_at' => now(),
            'previous_count' => $previousStreak,
        ]);
        $chatter->update(['current_streak' => 0]);

        event(new StreakBroken($chatter, $previousStreak));
    }
}
