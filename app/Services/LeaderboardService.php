<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\LeaderboardEntry;
use App\Models\RewardsLedger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class LeaderboardService
{
    // ── 5 leaderboard categories ─────────────────────────────────────
    public const CATEGORIES = ['xp', 'revenue', 'conversions', 'recruitment', 'streaks'];

    // ── Monthly prizes (cents) ───────────────────────────────────────
    private const MONTHLY_PRIZES = [
        1 => 10000,  // #1 = $100
        2 => 5000,   // #2 = $50
        3 => 2500,   // #3 = $25
    ];

    // ── Anti-gaming thresholds ───────────────────────────────────────
    private const MAX_DAILY_XP = 500;
    private const MAX_DAILY_CONVERSIONS = 20;
    private const MIN_REVENUE_PER_SALE = 100; // $1 minimum to count

    public function updateScore(Chatter $chatter, string $category, int $amount): void
    {
        if (!in_array($category, self::CATEGORIES)) {
            return;
        }

        // Anti-gaming: check daily cap for XP and conversions
        if ($this->isAntiGamingBlocked($chatter, $category, $amount)) {
            Log::info("Anti-gaming blocked: chatter {$chatter->id}, category {$category}, amount {$amount}");
            return;
        }

        $weekKey = now()->format('Y-\WW');
        $monthKey = now()->format('Y-m');

        Redis::zincrby("leaderboard:{$category}:weekly:{$weekKey}", $amount, $chatter->id);
        Redis::zincrby("leaderboard:{$category}:monthly:{$monthKey}", $amount, $chatter->id);
        Redis::zincrby("leaderboard:{$category}:alltime", $amount, $chatter->id);

        if ($chatter->country) {
            Redis::zincrby("leaderboard:{$category}:country:{$chatter->country}:weekly:{$weekKey}", $amount, $chatter->id);
        }

        // Track daily totals for anti-gaming
        $dailyKey = "leaderboard:daily:{$category}:{$chatter->id}:" . now()->toDateString();
        Redis::incrby($dailyKey, $amount);
        Redis::expire($dailyKey, 172800); // 48h TTL
    }

    /**
     * Convenience: update XP leaderboard (backward-compatible).
     */
    public function updateXpScore(Chatter $chatter, int $xpEarned): void
    {
        $this->updateScore($chatter, 'xp', $xpEarned);
    }

    /**
     * Record a sale for revenue + conversions leaderboards.
     */
    public function recordSale(Chatter $chatter, int $revenueCents): void
    {
        // Anti-gaming: ignore suspiciously low revenue
        if ($revenueCents < self::MIN_REVENUE_PER_SALE) {
            return;
        }

        $this->updateScore($chatter, 'revenue', $revenueCents);
        $this->updateScore($chatter, 'conversions', 1);
    }

    /**
     * Record a recruitment for recruitment leaderboard.
     */
    public function recordRecruitment(Chatter $chatter): void
    {
        $this->updateScore($chatter, 'recruitment', 1);
    }

    /**
     * Update streak score (called daily, reflects current streak count).
     */
    public function updateStreakScore(Chatter $chatter): void
    {
        $weekKey = now()->format('Y-\WW');
        $monthKey = now()->format('Y-m');

        // For streaks, we SET the score (not increment) — it's the current streak count
        Redis::zadd("leaderboard:streaks:weekly:{$weekKey}", $chatter->current_streak, $chatter->id);
        Redis::zadd("leaderboard:streaks:monthly:{$monthKey}", $chatter->current_streak, $chatter->id);
        Redis::zadd("leaderboard:streaks:alltime", $chatter->current_streak, $chatter->id);
    }

    public function getRank(Chatter $chatter, string $category = 'xp', string $period = 'weekly'): ?int
    {
        $key = $this->redisKey($category, $period);
        $rank = Redis::zrevrank($key, $chatter->id);
        return $rank !== null ? $rank + 1 : null;
    }

    public function getTopChatters(string $category = 'xp', string $period = 'weekly', int $limit = 50): array
    {
        $key = $this->redisKey($category, $period);
        return Redis::zrevrange($key, 0, $limit - 1, 'WITHSCORES') ?: [];
    }

    public function refreshLeaderboard(): void
    {
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY mv_leaderboard_weekly');
    }

    /**
     * Persist all categories to database for historical tracking.
     */
    public function persistToDatabase(): void
    {
        $periods = [
            'weekly' => now()->format('Y-\WW'),
            'monthly' => now()->format('Y-m'),
        ];

        foreach (self::CATEGORIES as $category) {
            foreach ($periods as $type => $key) {
                $redisKey = "leaderboard:{$category}:{$type}:{$key}";
                $entries = Redis::zrevrange($redisKey, 0, -1, 'WITHSCORES') ?: [];

                $rank = 0;
                foreach ($entries as $chatterId => $score) {
                    $rank++;
                    LeaderboardEntry::updateOrCreate(
                        [
                            'chatter_id' => $chatterId,
                            'period_type' => $type,
                            'period_key' => $key,
                            'metric' => $category,
                        ],
                        [
                            'value' => (int) $score,
                            'rank' => $rank,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Award monthly prizes to top 3 per category.
     * Called at month end by ProcessMonthlyPrizes command.
     */
    public function awardMonthlyPrizes(string $monthKey): array
    {
        $awarded = [];

        foreach (self::CATEGORIES as $category) {
            $redisKey = "leaderboard:{$category}:monthly:{$monthKey}";
            $top = Redis::zrevrange($redisKey, 0, 2, 'WITHSCORES') ?: [];

            $rank = 0;
            foreach ($top as $chatterId => $score) {
                $rank++;
                $prizeCents = self::MONTHLY_PRIZES[$rank] ?? 0;
                if ($prizeCents <= 0 || (int) $score <= 0) {
                    continue;
                }

                $chatter = Chatter::find($chatterId);
                if (!$chatter) {
                    continue;
                }

                // Anti-gaming: verify score is legitimate
                if (!$this->isScoreLegitimate($chatter, $category, (int) $score)) {
                    Log::warning("Anti-gaming: suspicious score for chatter {$chatterId} in {$category}", [
                        'score' => $score,
                        'rank' => $rank,
                    ]);
                    continue;
                }

                $chatter->increment('balance_cents', $prizeCents);

                RewardsLedger::create([
                    'chatter_id' => $chatter->id,
                    'reward_type' => 'leaderboard_prize',
                    'amount' => $prizeCents,
                    'source' => 'leaderboard',
                    'description' => "#{$rank} {$category} leaderboard — {$monthKey}",
                ]);

                $awarded[] = [
                    'chatter_id' => $chatterId,
                    'category' => $category,
                    'rank' => $rank,
                    'prize_cents' => $prizeCents,
                ];
            }
        }

        return $awarded;
    }

    // ── Anti-gaming ──────────────────────────────────────────────────

    private function isAntiGamingBlocked(Chatter $chatter, string $category, int $amount): bool
    {
        $dailyKey = "leaderboard:daily:{$category}:{$chatter->id}:" . now()->toDateString();
        $currentDaily = (int) Redis::get($dailyKey) ?: 0;

        return match ($category) {
            'xp' => ($currentDaily + $amount) > self::MAX_DAILY_XP,
            'conversions' => ($currentDaily + $amount) > self::MAX_DAILY_CONVERSIONS,
            default => false,
        };
    }

    /**
     * Verify a monthly score isn't suspicious before awarding prizes.
     */
    private function isScoreLegitimate(Chatter $chatter, string $category, int $score): bool
    {
        return match ($category) {
            // XP: max 500/day × 31 days = 15,500 theoretical max
            'xp' => $score <= self::MAX_DAILY_XP * 31,
            // Conversions: max 20/day × 31 = 620 theoretical max
            'conversions' => $score <= self::MAX_DAILY_CONVERSIONS * 31,
            // Revenue: cross-check with lifetime_earnings
            'revenue' => $score <= $chatter->lifetime_earnings_cents,
            default => true,
        };
    }

    private function redisKey(string $category, string $period): string
    {
        return match ($period) {
            'weekly' => "leaderboard:{$category}:weekly:" . now()->format('Y-\WW'),
            'monthly' => "leaderboard:{$category}:monthly:" . now()->format('Y-m'),
            'alltime' => "leaderboard:{$category}:alltime",
            default => "leaderboard:{$category}:weekly:" . now()->format('Y-\WW'),
        };
    }
}
