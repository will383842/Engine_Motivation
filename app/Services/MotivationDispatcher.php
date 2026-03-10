<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\MessageLog;
use App\Models\SuppressionList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MotivationDispatcher
{
    // ── Hard limits per channel ──────────────────────────────────────
    private const LIMITS = [
        'telegram' => [
            'daily' => 3,          // max 3 msg/chatter/day (4 if urgent)
            'daily_urgent' => 4,
            'weekly' => 15,
            'gap_seconds' => 7200, // 2h minimum gap
            'quiet_start' => '23:00',
            'quiet_end' => '07:00',
        ],
        'whatsapp' => [
            'daily' => 1,          // max 1 msg/chatter/day — NEVER more
            'daily_urgent' => 1,   // even urgent = 1 for WhatsApp
            'weekly' => 4,
            'gap_seconds' => 14400, // 4h minimum gap
            'quiet_start' => '22:00',
            'quiet_end' => '09:00',
        ],
        'dashboard' => [
            'daily' => 10,
            'daily_urgent' => 10,
            'weekly' => 70,
            'gap_seconds' => 0,
            'quiet_start' => null,
            'quiet_end' => null,
        ],
    ];

    public function __construct(
        private TelegramSender $telegramSender,
        private WhatsAppSender $whatsAppSender,
        private FatigueScoreService $fatigueService,
        private SmartSendService $smartSendService,
    ) {}

    public function send(
        Chatter $chatter,
        string $templateSlug,
        ?string $channel = null,
        array $variables = [],
        bool $urgent = false,
    ): ?MessageLog {
        $channel = $channel ?? $this->resolveChannel($chatter);

        if (!$this->canSend($chatter, $channel, $urgent)) {
            Log::info("Cannot send to chatter {$chatter->id} on {$channel}: blocked by limits");
            return null;
        }

        $messageLog = MessageLog::create([
            'chatter_id' => $chatter->id,
            'channel' => $channel,
            'status' => 'pending',
            'source_type' => 'motivation',
            'body' => $templateSlug,
            'created_at' => now(),
        ]);

        try {
            $result = match ($channel) {
                'telegram' => $this->telegramSender->send($chatter, $templateSlug, $variables),
                'whatsapp' => $this->whatsAppSender->send($chatter, $templateSlug, $variables),
                default => null,
            };

            if ($result) {
                $messageLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'external_msg_id' => $result['message_id'] ?? null,
                    'cost_cents' => $result['cost_cents'] ?? 0,
                ]);

                // Record send for rate limiting
                $this->recordSend($chatter, $channel);
            }

            return $messageLog;
        } catch (\Throwable $e) {
            $messageLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_code' => $e->getCode() ?: 'unknown',
            ]);
            Log::error("Failed to send to {$chatter->id}: {$e->getMessage()}");
            return $messageLog;
        }
    }

    /**
     * Check ALL conditions before sending: suppression, lifecycle, opt-in,
     * fatigue, daily/weekly limits, gap, quiet hours.
     */
    public function canSend(Chatter $chatter, string $channel, bool $urgent = false): bool
    {
        // 1. Suppression list
        if ($this->isSuppressed($chatter->id, $channel)) {
            return false;
        }

        // 2. Lifecycle state
        if ($chatter->lifecycle_state === 'sunset') {
            return false;
        }

        // 3. Opt-in check
        if (!$chatter->isOptedIn($channel)) {
            return false;
        }

        // 4. Fatigue score
        $fatigue = $this->fatigueService->getMultiplier($chatter, $channel);
        if ($fatigue <= 0) {
            return false;
        }

        // 5. Daily limit
        if ($this->isDailyLimitReached($chatter, $channel, $urgent)) {
            return false;
        }

        // 6. Weekly limit
        if ($this->isWeeklyLimitReached($chatter, $channel)) {
            return false;
        }

        // 7. Minimum gap between messages
        if ($this->isInCooldown($chatter, $channel)) {
            return false;
        }

        // 8. Quiet hours (timezone-aware)
        if ($this->isQuietHours($chatter, $channel)) {
            return false;
        }

        return true;
    }

    public function resolveChannel(Chatter $chatter): string
    {
        // Telegram exclusive if telegram_id exists
        if ($chatter->telegram_id) {
            return 'telegram';
        }
        if ($chatter->whatsapp_opted_in && $chatter->whatsapp_phone) {
            return 'whatsapp';
        }
        return 'dashboard';
    }

    // ── Rate limiting helpers ────────────────────────────────────────

    private function isDailyLimitReached(Chatter $chatter, string $channel, bool $urgent): bool
    {
        $limits = self::LIMITS[$channel] ?? self::LIMITS['dashboard'];
        $maxDaily = $urgent ? $limits['daily_urgent'] : $limits['daily'];
        $count = (int) Cache::get("msg_count:daily:{$chatter->id}:{$channel}", 0);
        return $count >= $maxDaily;
    }

    private function isWeeklyLimitReached(Chatter $chatter, string $channel): bool
    {
        $limits = self::LIMITS[$channel] ?? self::LIMITS['dashboard'];
        $count = (int) Cache::get("msg_count:weekly:{$chatter->id}:{$channel}", 0);
        return $count >= $limits['weekly'];
    }

    private function isInCooldown(Chatter $chatter, string $channel): bool
    {
        $limits = self::LIMITS[$channel] ?? self::LIMITS['dashboard'];
        if ($limits['gap_seconds'] <= 0) {
            return false;
        }
        return Cache::has("msg_cooldown:{$chatter->id}:{$channel}");
    }

    private function isQuietHours(Chatter $chatter, string $channel): bool
    {
        $limits = self::LIMITS[$channel] ?? self::LIMITS['dashboard'];
        if (!$limits['quiet_start'] || !$limits['quiet_end']) {
            return false;
        }

        // Check custom quiet hours from notification preferences first
        $pref = $chatter->notificationPreferences()->where('channel', $channel)->first();
        $quietStart = $pref?->quiet_hours_start ?? $limits['quiet_start'];
        $quietEnd = $pref?->quiet_hours_end ?? $limits['quiet_end'];

        $tz = $chatter->timezone ?: 'UTC';
        $now = Carbon::now($tz);
        $start = $now->copy()->setTimeFromTimeString($quietStart);
        $end = $now->copy()->setTimeFromTimeString($quietEnd);

        // Handle overnight quiet hours (23:00 - 07:00)
        if ($start->gt($end)) {
            return $now->gte($start) || $now->lte($end);
        }
        return $now->between($start, $end);
    }

    private function isSuppressed(string $chatterId, string $channel): bool
    {
        return Cache::remember("suppressed:{$chatterId}:{$channel}", 300, function () use ($chatterId, $channel) {
            return SuppressionList::where('chatter_id', $chatterId)
                ->where(fn ($q) => $q->where('channel', $channel)->orWhere('channel', 'all'))
                ->whereNull('lifted_at')
                ->exists();
        });
    }

    /**
     * Record a send for daily/weekly counters + cooldown.
     */
    private function recordSend(Chatter $chatter, string $channel): void
    {
        $limits = self::LIMITS[$channel] ?? self::LIMITS['dashboard'];

        // Daily counter (expires at midnight chatter timezone)
        $dailyKey = "msg_count:daily:{$chatter->id}:{$channel}";
        $tz = $chatter->timezone ?: 'UTC';
        $secondsUntilMidnight = Carbon::now($tz)->endOfDay()->diffInSeconds(Carbon::now($tz));
        Cache::increment($dailyKey);
        Cache::put($dailyKey, (int) Cache::get($dailyKey, 0), $secondsUntilMidnight);

        // Weekly counter (expires Monday 00:00)
        $weeklyKey = "msg_count:weekly:{$chatter->id}:{$channel}";
        $secondsUntilMonday = Carbon::now($tz)->endOfWeek()->diffInSeconds(Carbon::now($tz));
        Cache::increment($weeklyKey);
        Cache::put($weeklyKey, (int) Cache::get($weeklyKey, 0), $secondsUntilMonday);

        // Gap cooldown
        if ($limits['gap_seconds'] > 0) {
            Cache::put("msg_cooldown:{$chatter->id}:{$channel}", true, $limits['gap_seconds']);
        }
    }
}
