<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WhatsAppNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WhatsAppCircuitBreaker
{
    private const STATE_OPEN = 'open';
    private const STATE_REDUCE = 'reduce';
    private const STATE_PAUSE = 'pause';
    private const STATE_STOP = 'stop';

    public function canSend(WhatsAppNumber $number): bool
    {
        $state = $this->getState($number);
        return $state === self::STATE_OPEN || $state === self::STATE_REDUCE;
    }

    public function getState(WhatsAppNumber $number): string
    {
        return Redis::get("wa_num:{$number->id}:circuit_breaker:state") ?? self::STATE_OPEN;
    }

    public function recordSuccess(WhatsAppNumber $number): void
    {
        Redis::incr("wa_num:{$number->id}:sent_today");
        Redis::expire("wa_num:{$number->id}:sent_today", 86400);
        Redis::del("wa_num:{$number->id}:consecutive_failures");
    }

    public function recordFailure(WhatsAppNumber $number, int $errorCode): void
    {
        $failures = Redis::incr("wa_num:{$number->id}:consecutive_failures");
        Redis::incr("wa_num:{$number->id}:blocked_24h");
        Redis::expire("wa_num:{$number->id}:blocked_24h", 86400);

        $sent = (int) Redis::get("wa_num:{$number->id}:sent_today") ?: 1;
        $blocked = (int) Redis::get("wa_num:{$number->id}:blocked_24h") ?: 0;
        $blockRate = $blocked / $sent;

        $thresholds = config('whatsapp.circuit_breaker');

        $newState = match (true) {
            $blockRate >= $thresholds['critical_threshold'] || $failures >= 10 => self::STATE_STOP,
            $blockRate >= $thresholds['red_threshold'] || $failures >= 5 => self::STATE_PAUSE,
            $blockRate >= $thresholds['yellow_threshold'] || $failures >= 3 => self::STATE_REDUCE,
            default => self::STATE_OPEN,
        };

        $currentState = $this->getState($number);
        if ($newState !== $currentState) {
            $this->setState($number, $newState);
            Log::warning("WhatsApp circuit breaker state changed: {$number->phone_number} {$currentState} -> {$newState}");
        }
    }

    public function setState(WhatsAppNumber $number, string $state): void
    {
        Redis::set("wa_num:{$number->id}:circuit_breaker:state", $state);
        $cooldown = config('whatsapp.circuit_breaker.cool_down_minutes', 60);

        if ($state !== self::STATE_OPEN) {
            Redis::set("wa_num:{$number->id}:circuit_breaker:until", now()->addMinutes($cooldown)->timestamp);
        }

        $number->update([
            'circuit_breaker_state' => $state,
            'circuit_breaker_until' => $state !== self::STATE_OPEN ? now()->addMinutes($cooldown) : null,
        ]);
    }

    public function checkAndRecover(WhatsAppNumber $number): void
    {
        $until = Redis::get("wa_num:{$number->id}:circuit_breaker:until");
        if ($until && now()->timestamp >= (int) $until) {
            $this->setState($number, self::STATE_OPEN);
            Log::info("WhatsApp circuit breaker recovered: {$number->phone_number}");
        }
    }
}
