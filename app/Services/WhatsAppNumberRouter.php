<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\WhatsAppNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class WhatsAppNumberRouter
{
    public function __construct(
        private WhatsAppCircuitBreaker $circuitBreaker,
    ) {}

    public function resolve(Chatter $chatter): ?WhatsAppNumber
    {
        // Check cached assignment
        $cachedId = Redis::get("chatter:{$chatter->id}:wa_num");
        if ($cachedId) {
            $number = WhatsAppNumber::find($cachedId);
            if ($number && $number->is_active && $this->circuitBreaker->canSend($number)) {
                return $number;
            }
        }

        // Health-based routing
        $number = $this->selectBestNumber($chatter);
        if ($number) {
            Redis::setex("chatter:{$chatter->id}:wa_num", 1800, $number->id);
            $chatter->update(['last_whatsapp_number_id' => $number->id]);
        }

        return $number;
    }

    private function selectBestNumber(Chatter $chatter): ?WhatsAppNumber
    {
        $numbers = WhatsAppNumber::where('is_active', true)
            ->where('circuit_breaker_state', '!=', 'stop')
            ->orderByDesc('health_score')
            ->get();

        foreach ($numbers as $number) {
            $sentToday = (int) Redis::get("wa_num:{$number->id}:sent_today") ?: 0;
            if ($sentToday >= $number->current_daily_limit) {
                continue;
            }

            // Geo-matching: prefer number from same country
            if ($chatter->country && $number->country_code === $chatter->country) {
                return $number;
            }
        }

        // Fallback: any available number
        return $numbers->first(function ($number) {
            $sentToday = (int) Redis::get("wa_num:{$number->id}:sent_today") ?: 0;
            return $sentToday < $number->current_daily_limit;
        });
    }
}
