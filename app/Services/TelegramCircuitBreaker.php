<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelegramBot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TelegramCircuitBreaker
{
    // Retry backoff: [0s, 30s, 300s] (not inverted [1, 5, 30])
    public const RETRY_DELAYS = [0, 30, 300];

    // Circuit breaker threshold: PAUSE at 20 failures (not 50)
    private const PAUSE_15MIN_THRESHOLD = 20;
    private const PAUSE_1H_THRESHOLD = 50;

    public function canSend(TelegramBot $bot): bool
    {
        $state = $this->getState($bot);
        return $state === 'open';
    }

    public function getState(TelegramBot $bot): string
    {
        return Redis::get("tg_bot:{$bot->id}:circuit:state") ?? 'open';
    }

    public function recordSuccess(TelegramBot $bot): void
    {
        Redis::incr("tg_bot:{$bot->id}:sent_today");
        Redis::expire("tg_bot:{$bot->id}:sent_today", 86400);
        Redis::del("tg_bot:{$bot->id}:consecutive_failures");
        $bot->increment('total_sent');
    }

    public function recordFailure(TelegramBot $bot, int $errorCode): void
    {
        $failures5m = Redis::incr("tg_bot:{$bot->id}:failures_5min");
        Redis::expire("tg_bot:{$bot->id}:failures_5min", 300);
        Redis::incr("tg_bot:{$bot->id}:failures_1h");
        Redis::expire("tg_bot:{$bot->id}:failures_1h", 3600);

        $bot->increment('total_failed');
        $bot->increment('consecutive_failures');

        $newState = match (true) {
            $failures5m >= self::PAUSE_1H_THRESHOLD => 'pause_1h',
            $failures5m >= self::PAUSE_15MIN_THRESHOLD => 'pause_15min',
            default => 'open',
        };

        if ($newState !== 'open') {
            Redis::set("tg_bot:{$bot->id}:circuit:state", $newState);
            $ttl = $newState === 'pause_1h' ? 3600 : 900;
            Redis::expire("tg_bot:{$bot->id}:circuit:state", $ttl);
            Log::warning("Telegram circuit breaker: {$bot->bot_username} -> {$newState} (failures: {$failures5m}/5min)");
        }
    }

    /**
     * Get retry delay for a given attempt (0-indexed).
     */
    public static function getRetryDelay(int $attempt): int
    {
        return self::RETRY_DELAYS[$attempt] ?? self::RETRY_DELAYS[array_key_last(self::RETRY_DELAYS)];
    }
}
