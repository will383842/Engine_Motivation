<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\TelegramBot;
use Illuminate\Support\Facades\Redis;

class TelegramBotRouter
{
    // Pool hierarchy: primary → secondary → standby
    public const POOL_PRIMARY = 'primary';
    public const POOL_SECONDARY = 'secondary';
    public const POOL_STANDBY = 'standby';

    private const POOL_ORDER = [self::POOL_PRIMARY, self::POOL_SECONDARY, self::POOL_STANDBY];

    public function __construct(
        private TelegramCircuitBreaker $circuitBreaker,
    ) {}

    public function resolve(Chatter $chatter): ?TelegramBot
    {
        // Sticky assignment: same bot for same chatter
        $cachedId = Redis::get("chatter:{$chatter->id}:tg_bot");
        if ($cachedId) {
            $bot = TelegramBot::find($cachedId);
            if ($bot && $bot->is_active && $this->circuitBreaker->canSend($bot)) {
                return $bot;
            }
        }

        // Existing assignment from DB
        if ($chatter->assigned_telegram_bot_id) {
            $bot = TelegramBot::find($chatter->assigned_telegram_bot_id);
            if ($bot && $bot->is_active && $this->circuitBreaker->canSend($bot)) {
                Redis::setex("chatter:{$chatter->id}:tg_bot", 3600, $bot->id);
                return $bot;
            }
        }

        // Assign new bot with pool hierarchy: primary → secondary → standby
        $bot = $this->resolveFromPool($chatter);

        if ($bot) {
            $bot->increment('assigned_chatters_count');
            $chatter->update(['assigned_telegram_bot_id' => $bot->id]);
            Redis::setex("chatter:{$chatter->id}:tg_bot", 3600, $bot->id);
        }

        return $bot;
    }

    /**
     * Try each pool tier in order: primary → secondary → standby.
     */
    private function resolveFromPool(Chatter $chatter): ?TelegramBot
    {
        foreach (self::POOL_ORDER as $pool) {
            $bot = TelegramBot::where('is_active', true)
                ->where('is_restricted', false)
                ->where('pool_tier', $pool)
                ->orderBy('assigned_chatters_count')
                ->get()
                ->first(fn (TelegramBot $b) => $this->circuitBreaker->canSend($b));

            if ($bot) {
                return $bot;
            }
        }

        // Last resort: any active bot regardless of pool_tier
        return TelegramBot::where('is_active', true)
            ->where('is_restricted', false)
            ->orderBy('assigned_chatters_count')
            ->get()
            ->first(fn (TelegramBot $b) => $this->circuitBreaker->canSend($b));
    }
}
