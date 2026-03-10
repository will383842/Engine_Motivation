<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Social proof notifications: display recent achievements to motivate chatters.
 * Privacy: first name + initial, amounts capped at $50, 24h TTL.
 */
class SocialProofService
{
    private const TTL_SECONDS = 86400; // 24h
    private const MAX_AMOUNT_DISPLAY = 5000; // $50 in cents
    private const MAX_NOTIFICATIONS = 20;
    private const REDIS_KEY = 'social_proof:feed';

    // 7 notification types
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_NEW_CHATTER = 'new_chatter';
    public const TYPE_LEVEL_UP = 'level_up';
    public const TYPE_STREAK_MILESTONE = 'streak_milestone';
    public const TYPE_BADGE_EARNED = 'badge_earned';
    public const TYPE_FIRST_SALE = 'first_sale';
    public const TYPE_RECRUITMENT = 'recruitment';

    /**
     * Push a social proof notification.
     */
    public function push(string $type, Chatter $chatter, array $data = []): void
    {
        $notification = [
            'type' => $type,
            'display_name' => $this->anonymizeName($chatter),
            'country' => $chatter->country,
            'data' => $this->sanitizeData($type, $data),
            'timestamp' => now()->timestamp,
        ];

        // Push to Redis list with TTL
        Redis::lpush(self::REDIS_KEY, json_encode($notification));
        Redis::ltrim(self::REDIS_KEY, 0, self::MAX_NOTIFICATIONS - 1);
        Redis::expire(self::REDIS_KEY, self::TTL_SECONDS);
    }

    /**
     * Get recent social proof notifications for display.
     */
    public function getRecent(int $limit = 10): array
    {
        $raw = Redis::lrange(self::REDIS_KEY, 0, $limit - 1);
        return array_map(fn ($item) => json_decode($item, true), $raw ?: []);
    }

    /**
     * Push commission notification.
     */
    public function pushCommission(Chatter $chatter, int $amountCents): void
    {
        $this->push(self::TYPE_COMMISSION, $chatter, [
            'amount_cents' => min($amountCents, self::MAX_AMOUNT_DISPLAY),
        ]);
    }

    /**
     * Push new chatter joined notification.
     */
    public function pushNewChatter(Chatter $chatter): void
    {
        $this->push(self::TYPE_NEW_CHATTER, $chatter);
    }

    /**
     * Push level up notification.
     */
    public function pushLevelUp(Chatter $chatter, int $newLevel): void
    {
        $this->push(self::TYPE_LEVEL_UP, $chatter, [
            'level' => $newLevel,
            'tier_name' => LevelService::getTierInfo($newLevel)['name'] ?? '',
        ]);
    }

    /**
     * Push streak milestone notification.
     */
    public function pushStreakMilestone(Chatter $chatter, int $days): void
    {
        $this->push(self::TYPE_STREAK_MILESTONE, $chatter, [
            'days' => $days,
        ]);
    }

    /**
     * Push badge earned notification.
     */
    public function pushBadgeEarned(Chatter $chatter, string $badgeSlug): void
    {
        $this->push(self::TYPE_BADGE_EARNED, $chatter, [
            'badge' => $badgeSlug,
        ]);
    }

    /**
     * Push first sale notification.
     */
    public function pushFirstSale(Chatter $chatter): void
    {
        $this->push(self::TYPE_FIRST_SALE, $chatter);
    }

    /**
     * Push recruitment notification.
     */
    public function pushRecruitment(Chatter $chatter): void
    {
        $this->push(self::TYPE_RECRUITMENT, $chatter);
    }

    // ── Privacy ──────────────────────────────────────────────────────

    /**
     * Anonymize: "Jean D." format (first name + initial of last name).
     */
    private function anonymizeName(Chatter $chatter): string
    {
        $name = $chatter->display_name ?? 'Chatter';
        $parts = explode(' ', trim($name));

        if (count($parts) >= 2) {
            $firstName = $parts[0];
            $lastInitial = mb_strtoupper(mb_substr($parts[1], 0, 1));
            return "{$firstName} {$lastInitial}.";
        }

        // Single name — just show first 6 chars + ...
        return mb_substr($name, 0, 6) . (mb_strlen($name) > 6 ? '...' : '');
    }

    /**
     * Sanitize data: cap amounts at $50, remove sensitive fields.
     */
    private function sanitizeData(string $type, array $data): array
    {
        if (isset($data['amount_cents'])) {
            $data['amount_cents'] = min((int) $data['amount_cents'], self::MAX_AMOUNT_DISPLAY);
        }

        // Remove any sensitive fields that might leak through
        unset($data['email'], $data['phone'], $data['whatsapp_phone'], $data['uid']);

        return $data;
    }
}
