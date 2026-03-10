<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\RewardsLedger;
use App\Events\LevelUp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class LevelService
{
    // ── 8 Level Tiers ────────────────────────────────────────────────
    // level => [name, min_xp, reward_cents]
    public const TIERS = [
        1 => ['name' => 'Novice',       'min_xp' => 0,      'reward_cents' => 0],
        2 => ['name' => 'Apprenti',     'min_xp' => 500,    'reward_cents' => 200],    // $2
        3 => ['name' => 'Confirmé',     'min_xp' => 2000,   'reward_cents' => 500],    // $5
        4 => ['name' => 'Expert',       'min_xp' => 5000,   'reward_cents' => 1000],   // $10
        5 => ['name' => 'Maître',       'min_xp' => 12000,  'reward_cents' => 2000],   // $20
        6 => ['name' => 'Champion',     'min_xp' => 25000,  'reward_cents' => 3000],   // $30
        7 => ['name' => 'Légende',      'min_xp' => 50000,  'reward_cents' => 5000],   // $50
        8 => ['name' => 'Immortel',     'min_xp' => 100000, 'reward_cents' => 10000],  // $100
    ];

    // ── 14 XP Sources with daily caps ────────────────────────────────
    // source => [xp_amount, daily_cap]
    public const XP_SOURCES = [
        'sale_completed'       => ['xp' => 50,  'daily_cap' => 500],
        'first_sale'           => ['xp' => 200, 'daily_cap' => 200],  // one-time effectively
        'referral_signup'      => ['xp' => 30,  'daily_cap' => 150],
        'referral_activated'   => ['xp' => 100, 'daily_cap' => 300],
        'click_tracked'        => ['xp' => 5,   'daily_cap' => 50],
        'training_completed'   => ['xp' => 40,  'daily_cap' => 120],
        'telegram_linked'      => ['xp' => 50,  'daily_cap' => 50],   // one-time
        'zoom_attended'        => ['xp' => 75,  'daily_cap' => 150],
        'profile_updated'      => ['xp' => 10,  'daily_cap' => 10],   // one-time effectively
        'mission_completed'    => ['xp' => 25,  'daily_cap' => 100],
        'sweep_bonus'          => ['xp' => 50,  'daily_cap' => 50],
        'badge_earned'         => ['xp' => 30,  'daily_cap' => 150],
        'streak_milestone'     => ['xp' => 40,  'daily_cap' => 120],
        'captain_promoted'     => ['xp' => 150, 'daily_cap' => 150],  // one-time
    ];

    public function __construct(
        private LeaderboardService $leaderboardService,
    ) {}

    /**
     * Award XP from a specific source, respecting daily caps.
     * Returns actual XP awarded (0 if capped).
     */
    public function awardXp(Chatter $chatter, string $source, ?int $overrideXp = null): int
    {
        $config = self::XP_SOURCES[$source] ?? null;
        if (!$config) {
            Log::warning("Unknown XP source: {$source}");
            return 0;
        }

        $xpAmount = $overrideXp ?? $config['xp'];
        $dailyCap = $config['daily_cap'];

        // Check daily cap
        $dailyKey = "xp:daily:{$source}:{$chatter->id}:" . now()->toDateString();
        $currentDaily = (int) Redis::get($dailyKey) ?: 0;

        if ($currentDaily >= $dailyCap) {
            return 0;
        }

        // Clamp to remaining cap
        $xpAwarded = min($xpAmount, $dailyCap - $currentDaily);

        // Apply streak multiplier
        $streakMultiplier = $this->getStreakMultiplier($chatter->current_streak ?? 0);
        $xpAwarded = (int) round($xpAwarded * $streakMultiplier);

        // Award XP
        $chatter->increment('total_xp', $xpAwarded);

        // Track daily usage
        Redis::incrby($dailyKey, $xpAwarded);
        Redis::expire($dailyKey, 172800); // 48h TTL

        // Update leaderboard
        $this->leaderboardService->updateXpScore($chatter, $xpAwarded);

        // Check level up
        $this->checkLevelUp($chatter->fresh());

        return $xpAwarded;
    }

    /**
     * Check if chatter qualifies for a level up and award rewards.
     */
    public function checkLevelUp(Chatter $chatter): void
    {
        $currentLevel = $chatter->level ?? 1;
        $totalXp = $chatter->total_xp ?? 0;

        $newLevel = $this->calculateLevel($totalXp);

        if ($newLevel > $currentLevel) {
            $chatter->update(['level' => $newLevel]);

            // Award level-up reward
            $rewardCents = self::TIERS[$newLevel]['reward_cents'] ?? 0;
            if ($rewardCents > 0) {
                $chatter->increment('balance_cents', $rewardCents);

                RewardsLedger::create([
                    'chatter_id' => $chatter->id,
                    'reward_type' => 'level_up',
                    'amount' => $rewardCents,
                    'source' => 'level',
                    'description' => "Level up to {$newLevel} (" . (self::TIERS[$newLevel]['name'] ?? '') . ")",
                ]);
            }

            event(new LevelUp($chatter, $currentLevel, $newLevel));

            Log::info("Chatter {$chatter->id} leveled up: {$currentLevel} → {$newLevel} (reward: {$rewardCents}c)");
        }
    }

    /**
     * Calculate level from total XP.
     */
    public function calculateLevel(int $totalXp): int
    {
        $level = 1;
        foreach (self::TIERS as $lvl => $tier) {
            if ($totalXp >= $tier['min_xp']) {
                $level = $lvl;
            }
        }
        return $level;
    }

    /**
     * Get tier info for a level.
     */
    public static function getTierInfo(int $level): array
    {
        return self::TIERS[$level] ?? self::TIERS[1];
    }

    /**
     * XP needed to reach next level.
     */
    public function xpToNextLevel(Chatter $chatter): int
    {
        $currentLevel = $chatter->level ?? 1;
        $nextLevel = min($currentLevel + 1, max(array_keys(self::TIERS)));
        $nextXp = self::TIERS[$nextLevel]['min_xp'] ?? PHP_INT_MAX;
        return max(0, $nextXp - ($chatter->total_xp ?? 0));
    }

    /**
     * Progress percentage toward next level (0-100).
     */
    public function progressPercent(Chatter $chatter): float
    {
        $currentLevel = $chatter->level ?? 1;
        $nextLevel = min($currentLevel + 1, max(array_keys(self::TIERS)));

        $currentMin = self::TIERS[$currentLevel]['min_xp'] ?? 0;
        $nextMin = self::TIERS[$nextLevel]['min_xp'] ?? $currentMin;

        $range = $nextMin - $currentMin;
        if ($range <= 0) {
            return 100.0; // Max level
        }

        $progress = ($chatter->total_xp ?? 0) - $currentMin;
        return min(100.0, round($progress / $range * 100, 1));
    }

    private function getStreakMultiplier(int $streakDays): float
    {
        // Match StreakService multipliers
        return match (true) {
            $streakDays >= 100 => 1.50,
            $streakDays >= 30  => 1.20,
            $streakDays >= 14  => 1.10,
            $streakDays >= 7   => 1.05,
            default            => 1.00,
        };
    }
}
