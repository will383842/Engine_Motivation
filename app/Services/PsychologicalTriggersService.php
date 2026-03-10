<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\RewardsLedger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Psychological triggers to boost engagement:
 * - Lucky Commission (1/20 chance, $1-$5 bonus)
 * - Mystery Box (XP 50% / Freeze 30% / $1-3 15% / rare badge 5%)
 * - Double or Nothing (next XP doubled or halved)
 * - Weekly Jackpot ($20 to one random active chatter)
 * - Spin the Wheel (daily chance for random reward)
 * - Endowed Progress (onboarding pre-fill: 2/12 steps, $50 tirelire, 15% level bar)
 */
class PsychologicalTriggersService
{
    // Lucky Commission: 1 in 20 chance (5%)
    private const LUCKY_COMMISSION_CHANCE = 20;
    private const LUCKY_COMMISSION_MIN_CENTS = 100;  // $1
    private const LUCKY_COMMISSION_MAX_CENTS = 500;  // $5

    // Weekly Jackpot
    private const JACKPOT_AMOUNT_CENTS = 2000; // $20

    // Endowed Progress defaults
    private const ENDOWED_STEPS_DONE = 2;
    private const ENDOWED_TOTAL_STEPS = 12;
    private const ENDOWED_TIRELIRE_CENTS = 5000; // $50 locked
    private const ENDOWED_LEVEL_PROGRESS_PERCENT = 15;

    public function __construct(
        private StreakService $streakService,
        private LevelService $levelService,
    ) {}

    // ── Lucky Commission ─────────────────────────────────────────────

    /**
     * Roll for lucky commission after a sale. 1/20 chance.
     * Returns bonus cents awarded (0 if not lucky).
     */
    public function rollLuckyCommission(Chatter $chatter): int
    {
        if (mt_rand(1, self::LUCKY_COMMISSION_CHANCE) !== 1) {
            return 0;
        }

        $bonusCents = mt_rand(self::LUCKY_COMMISSION_MIN_CENTS, self::LUCKY_COMMISSION_MAX_CENTS);
        // Round to nearest 50 cents
        $bonusCents = (int) (round($bonusCents / 50) * 50);

        $chatter->increment('balance_cents', $bonusCents);

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'lucky_commission',
            'amount' => $bonusCents,
            'source' => 'lucky_commission',
            'description' => "Lucky Commission! +\$" . number_format($bonusCents / 100, 2),
        ]);

        Log::info("Lucky Commission for chatter {$chatter->id}: +{$bonusCents}c");
        return $bonusCents;
    }

    // ── Mystery Box ──────────────────────────────────────────────────

    /**
     * Open a mystery box. Weighted random:
     * - 50% XP bonus (25-100 XP)
     * - 30% Streak freeze (+1 reserve)
     * - 15% Cash ($1-$3)
     * - 5% Rare badge
     *
     * Returns ['type' => ..., 'value' => ...]
     */
    public function openMysteryBox(Chatter $chatter): array
    {
        $dailyKey = "mystery_box:{$chatter->id}:" . now()->toDateString();
        if (Redis::get($dailyKey)) {
            return ['type' => 'already_opened', 'value' => 0];
        }

        $roll = mt_rand(1, 100);
        $result = match (true) {
            $roll <= 50 => $this->mysteryBoxXp($chatter),
            $roll <= 80 => $this->mysteryBoxFreeze($chatter),
            $roll <= 95 => $this->mysteryBoxCash($chatter),
            default => $this->mysteryBoxBadge($chatter),
        };

        Redis::setex($dailyKey, 172800, '1'); // 48h TTL

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'mystery_box',
            'amount' => $result['value'] ?? 0,
            'source' => 'mystery_box',
            'description' => "Mystery Box: {$result['type']} — {$result['description']}",
        ]);

        return $result;
    }

    private function mysteryBoxXp(Chatter $chatter): array
    {
        $xp = mt_rand(25, 100);
        $chatter->increment('total_xp', $xp);
        return ['type' => 'xp', 'value' => $xp, 'description' => "+{$xp} XP"];
    }

    private function mysteryBoxFreeze(Chatter $chatter): array
    {
        $streak = $chatter->streak;
        if ($streak) {
            $streak->increment('freeze_count_max');
        }
        return ['type' => 'freeze', 'value' => 1, 'description' => '+1 streak freeze'];
    }

    private function mysteryBoxCash(Chatter $chatter): array
    {
        $cents = mt_rand(1, 3) * 100; // $1, $2, or $3
        $chatter->increment('balance_cents', $cents);
        return ['type' => 'cash', 'value' => $cents, 'description' => "+\$" . ($cents / 100)];
    }

    private function mysteryBoxBadge(Chatter $chatter): array
    {
        // Award a random rare badge if not already earned
        $rareBadge = \App\Models\Badge::where('category', 'rare')
            ->whereNotIn('id', $chatter->badges()->pluck('badges.id'))
            ->inRandomOrder()
            ->first();

        if ($rareBadge) {
            $chatter->badges()->attach($rareBadge->id, ['awarded_at' => now()]);
            $chatter->increment('badges_count');
            return ['type' => 'badge', 'value' => 1, 'description' => "Rare badge: {$rareBadge->slug}"];
        }

        // Fallback to XP if all rare badges earned
        return $this->mysteryBoxXp($chatter);
    }

    // ── Double or Nothing ────────────────────────────────────────────

    /**
     * Activate double or nothing for next XP earning.
     * 50/50: XP doubled or halved.
     */
    public function activateDoubleOrNothing(Chatter $chatter): bool
    {
        $key = "double_or_nothing:{$chatter->id}";
        if (Redis::get($key)) {
            return false; // Already active
        }

        Redis::setex($key, 86400, '1'); // Active for 24h
        return true;
    }

    /**
     * Resolve double or nothing when XP is earned.
     * Returns multiplier: 2.0 (win) or 0.5 (lose).
     */
    public function resolveDoubleOrNothing(Chatter $chatter): float
    {
        $key = "double_or_nothing:{$chatter->id}";
        if (!Redis::get($key)) {
            return 1.0; // Not active
        }

        Redis::del($key);
        $won = mt_rand(0, 1) === 1;

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'double_or_nothing',
            'amount' => 0,
            'source' => 'double_or_nothing',
            'description' => $won ? 'Double or Nothing: WON (2x XP)' : 'Double or Nothing: LOST (0.5x XP)',
        ]);

        return $won ? 2.0 : 0.5;
    }

    // ── Weekly Jackpot ───────────────────────────────────────────────

    /**
     * Award $20 weekly jackpot to one random active chatter.
     * Called by ProcessWeeklyJackpot command.
     */
    public function awardWeeklyJackpot(): ?array
    {
        $weekKey = "jackpot:week:" . now()->format('Y-W');
        if (Redis::get($weekKey)) {
            return null; // Already awarded this week
        }

        $winner = Chatter::where('is_active', true)
            ->where('lifecycle_state', 'active')
            ->where('last_active_at', '>=', now()->subDays(7))
            ->inRandomOrder()
            ->first();

        if (!$winner) {
            return null;
        }

        $winner->increment('balance_cents', self::JACKPOT_AMOUNT_CENTS);

        RewardsLedger::create([
            'chatter_id' => $winner->id,
            'reward_type' => 'jackpot',
            'amount' => self::JACKPOT_AMOUNT_CENTS,
            'source' => 'weekly_jackpot',
            'description' => 'Weekly Jackpot Winner! +$' . (self::JACKPOT_AMOUNT_CENTS / 100),
        ]);

        Redis::setex($weekKey, 604800, $winner->id); // 7 days TTL

        Log::info("Weekly jackpot awarded to chatter {$winner->id}: \$" . (self::JACKPOT_AMOUNT_CENTS / 100));

        return [
            'chatter_id' => $winner->id,
            'display_name' => $winner->display_name,
            'amount_cents' => self::JACKPOT_AMOUNT_CENTS,
        ];
    }

    // ── Spin the Wheel ───────────────────────────────────────────────

    /**
     * Daily spin the wheel. Returns reward.
     * Segments: [5 XP, 10 XP, 25 XP, 50 XP, $0.50, $1, Freeze, Nothing]
     */
    public function spinTheWheel(Chatter $chatter): array
    {
        $dailyKey = "spin:{$chatter->id}:" . now()->toDateString();
        if (Redis::get($dailyKey)) {
            return ['segment' => 'already_spun', 'value' => 0];
        }

        $segments = [
            ['segment' => '5_xp',    'weight' => 25, 'type' => 'xp',    'value' => 5],
            ['segment' => '10_xp',   'weight' => 20, 'type' => 'xp',    'value' => 10],
            ['segment' => '25_xp',   'weight' => 15, 'type' => 'xp',    'value' => 25],
            ['segment' => '50_xp',   'weight' => 8,  'type' => 'xp',    'value' => 50],
            ['segment' => '50c',     'weight' => 10, 'type' => 'cash',   'value' => 50],
            ['segment' => '$1',      'weight' => 5,  'type' => 'cash',   'value' => 100],
            ['segment' => 'freeze',  'weight' => 7,  'type' => 'freeze', 'value' => 1],
            ['segment' => 'nothing', 'weight' => 10, 'type' => 'none',   'value' => 0],
        ];

        $totalWeight = array_sum(array_column($segments, 'weight'));
        $roll = mt_rand(1, $totalWeight);
        $cumulative = 0;
        $result = $segments[0];

        foreach ($segments as $seg) {
            $cumulative += $seg['weight'];
            if ($roll <= $cumulative) {
                $result = $seg;
                break;
            }
        }

        // Apply reward
        match ($result['type']) {
            'xp' => $chatter->increment('total_xp', $result['value']),
            'cash' => $chatter->increment('balance_cents', $result['value']),
            'freeze' => $chatter->streak?->increment('freeze_count_max'),
            default => null,
        };

        Redis::setex($dailyKey, 172800, '1');

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'spin_wheel',
            'amount' => $result['value'],
            'source' => 'spin_wheel',
            'description' => "Spin the Wheel: {$result['segment']}",
        ]);

        return $result;
    }

    // ── Endowed Progress ─────────────────────────────────────────────

    /**
     * Set up endowed progress for a new chatter:
     * - Onboarding starts at 2/12 (not 0/12)
     * - Tirelire pre-filled with $50 (locked until threshold)
     * - Level bar starts at 15%
     */
    public function applyEndowedProgress(Chatter $chatter): void
    {
        // Pre-fill level progress: give enough XP for 15% of level 2
        $level2Xp = LevelService::TIERS[2]['min_xp'] ?? 500;
        $endowedXp = (int) ($level2Xp * self::ENDOWED_LEVEL_PROGRESS_PERCENT / 100);
        $chatter->increment('total_xp', $endowedXp);

        // Pre-fill tirelire (locked balance)
        $chatter->update([
            'extra' => array_merge($chatter->extra ?? [], [
                'endowed_progress' => true,
                'locked_bonus_cents' => self::ENDOWED_TIRELIRE_CENTS,
                'onboarding_steps_done' => self::ENDOWED_STEPS_DONE,
                'onboarding_total_steps' => self::ENDOWED_TOTAL_STEPS,
            ]),
        ]);

        RewardsLedger::create([
            'chatter_id' => $chatter->id,
            'reward_type' => 'endowed_progress',
            'amount' => 0,
            'source' => 'onboarding',
            'description' => 'Endowed Progress: 2/12 steps, $50 tirelire locked, 15% level bar',
        ]);

        Log::info("Endowed progress applied to chatter {$chatter->id}: {$endowedXp} XP, \$50 locked tirelire");
    }
}
