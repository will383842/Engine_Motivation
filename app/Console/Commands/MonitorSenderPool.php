<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SenderHealthLog;
use App\Models\TelegramBot;
use App\Models\WhatsAppNumber;
use App\Services\AdminNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MonitorSenderPool extends Command
{
    protected $signature = 'pool:monitor {--channel=all : Channel to monitor (whatsapp, telegram, all)}';
    protected $description = 'Check health of all WhatsApp numbers and Telegram bots';

    public function handle(AdminNotifier $notifier): int
    {
        $channel = $this->option('channel');
        $this->info("Starting sender pool monitoring (channel: {$channel})...");

        $issues = [];

        if (in_array($channel, ['all', 'whatsapp'])) {
            $issues = array_merge($issues, $this->monitorWhatsApp());
        }

        if (in_array($channel, ['all', 'telegram'])) {
            $issues = array_merge($issues, $this->monitorTelegram());
        }

        // Alert if any critical issues
        $critical = array_filter($issues, fn ($i) => $i['severity'] === 'critical');
        if (count($critical) > 0) {
            $notifier->alert('critical', 'sender_pool', count($critical) . " critical sender pool issue(s):\n" . implode("\n", array_column($critical, 'message')), ['telegram', 'email', 'dashboard']);
        }

        $warnings = array_filter($issues, fn ($i) => $i['severity'] === 'warning');
        if (count($warnings) > 0) {
            $notifier->alert('warning', 'sender_pool', count($warnings) . " sender pool warning(s):\n" . implode("\n", array_column($warnings, 'message')), ['telegram', 'dashboard']);
        }

        $this->info("Pool monitoring complete: " . count($issues) . " issue(s) found");
        return Command::SUCCESS;
    }

    private function monitorWhatsApp(): array
    {
        $issues = [];
        $numbers = WhatsAppNumber::where('is_active', true)->get();

        $this->line("  WhatsApp: {$numbers->count()} active numbers");

        if ($numbers->isEmpty()) {
            $issues[] = ['severity' => 'critical', 'message' => 'No active WhatsApp numbers in pool'];
            return $issues;
        }

        $healthyCount = 0;

        foreach ($numbers as $number) {
            $state = $number->circuit_breaker_state ?? 'open';
            $sent = (int) Redis::get("wa_num:{$number->id}:sent_today") ?: 0;

            // Log health
            SenderHealthLog::create([
                'sender_type' => 'whatsapp',
                'sender_id' => $number->id,
                'sent_24h' => $sent,
                'delivered_24h' => 0,
                'blocked_24h' => (int) Redis::get("wa_num:{$number->id}:blocked_24h") ?: 0,
                'failed_24h' => 0,
                'block_rate' => 0,
                'quality_rating' => $number->quality_rating,
                'circuit_breaker_state' => $state,
                'health_score' => $number->health_score ?? 100,
                'checked_at' => now(),
            ]);

            if (in_array($state, ['pause', 'stop'])) {
                $issues[] = ['severity' => 'critical', 'message' => "WA {$number->phone_number}: circuit breaker {$state}"];
            } elseif ($state === 'reduce') {
                $issues[] = ['severity' => 'warning', 'message' => "WA {$number->phone_number}: circuit breaker reduce"];
            } else {
                $healthyCount++;
            }

            // Check warmup limit
            if ($number->warmup_week && $number->warmup_week < 8) {
                $limit = $number->current_daily_limit ?? 50;
                if ($sent > $limit * 0.7) {
                    $issues[] = ['severity' => 'warning', 'message' => "WA {$number->phone_number}: warm-up W{$number->warmup_week}, sent {$sent}/{$limit} (>70%)"];
                }
            }
        }

        if ($healthyCount === 0 && $numbers->count() > 0) {
            $issues[] = ['severity' => 'critical', 'message' => 'All WhatsApp numbers are degraded or paused'];
        }

        return $issues;
    }

    private function monitorTelegram(): array
    {
        $issues = [];
        $bots = TelegramBot::where('is_active', true)->get();

        $this->line("  Telegram: {$bots->count()} active bots");

        if ($bots->isEmpty()) {
            $issues[] = ['severity' => 'critical', 'message' => 'No active Telegram bots in pool'];
            return $issues;
        }

        foreach ($bots as $bot) {
            $failures = $bot->consecutive_failures ?? 0;

            // Log health
            SenderHealthLog::create([
                'sender_type' => 'telegram',
                'sender_id' => $bot->id,
                'sent_24h' => $bot->total_sent ?? 0,
                'delivered_24h' => 0,
                'blocked_24h' => 0,
                'failed_24h' => $bot->total_failed ?? 0,
                'block_rate' => 0,
                'quality_rating' => null,
                'circuit_breaker_state' => 'open',
                'health_score' => $bot->health_score ?? 100,
                'checked_at' => now(),
            ]);

            if ($failures >= 50) {
                $issues[] = ['severity' => 'critical', 'message' => "TG @{$bot->bot_username}: {$failures} consecutive failures"];
            } elseif ($failures >= 10) {
                $issues[] = ['severity' => 'warning', 'message' => "TG @{$bot->bot_username}: {$failures} consecutive failures"];
            }

            // Check if bot hasn't been checked recently
            if ($bot->last_health_check_at && $bot->last_health_check_at->diffInHours(now()) > 6) {
                $issues[] = ['severity' => 'warning', 'message' => "TG @{$bot->bot_username}: last health check " . $bot->last_health_check_at->diffForHumans()];
            }

            $bot->update(['last_health_check_at' => now()]);
        }

        return $issues;
    }
}
