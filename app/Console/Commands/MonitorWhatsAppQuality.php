<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SenderHealthLog;
use App\Models\WhatsAppHealthLog;
use App\Models\WhatsAppNumber;
use App\Services\AdminNotifier;
use App\Services\WhatsAppCircuitBreaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MonitorWhatsAppQuality extends Command
{
    protected $signature = 'whatsapp:monitor';
    protected $description = 'Check WhatsApp quality ratings and trigger circuit breakers';

    public function handle(WhatsAppCircuitBreaker $circuitBreaker, AdminNotifier $notifier): int
    {
        $this->info('Starting WhatsApp quality monitoring...');

        $numbers = WhatsAppNumber::where('is_active', true)->get();

        if ($numbers->isEmpty()) {
            $this->warn('No active WhatsApp numbers found');
            return Command::SUCCESS;
        }

        $totalHealthy = 0;
        $totalDegraded = 0;
        $totalPaused = 0;

        foreach ($numbers as $number) {
            $sent = (int) Redis::get("wa_num:{$number->id}:sent_today") ?: 0;
            $blocked = (int) Redis::get("wa_num:{$number->id}:blocked_24h") ?: 0;
            $delivered = max(0, $sent - $blocked);
            $blockRate = $sent > 0 ? $blocked / $sent : 0;

            // Calculate health score (0-100)
            $healthScore = 100;
            if ($blockRate > 0.01) $healthScore -= min(40, $blockRate * 400);
            $consecutiveFailures = (int) Redis::get("wa_num:{$number->id}:consecutive_failures");
            if ($consecutiveFailures > 0) $healthScore -= min(30, $consecutiveFailures * 5);
            if ($number->circuit_breaker_state !== 'open') $healthScore -= 30;
            $healthScore = max(0, $healthScore);

            // Update number record
            $number->update([
                'total_sent' => ($number->total_sent ?? 0) + $sent,
                'total_delivered' => ($number->total_delivered ?? 0) + $delivered,
                'total_blocked' => ($number->total_blocked ?? 0) + $blocked,
                'health_score' => $healthScore,
            ]);

            // Log health snapshot
            SenderHealthLog::create([
                'sender_type' => 'whatsapp',
                'sender_id' => $number->id,
                'sent_24h' => $sent,
                'delivered_24h' => $delivered,
                'blocked_24h' => $blocked,
                'failed_24h' => $blocked,
                'block_rate' => $blockRate,
                'quality_rating' => $number->quality_rating,
                'circuit_breaker_state' => $number->circuit_breaker_state ?? 'open',
                'health_score' => $healthScore,
                'checked_at' => now(),
            ]);

            // Check circuit breaker recovery
            $circuitBreaker->checkAndRecover($number);

            // Budget check
            $dailyBudget = config('whatsapp.daily_budget_cents', 5000);
            $costToday = (int) Redis::get("wa_num:{$number->id}:cost_today") ?: 0;
            if ($dailyBudget > 0 && $costToday >= $dailyBudget * 0.8) {
                $notifier->alert('warning', 'whatsapp_budget', "WhatsApp number {$number->phone_number}: budget at " . round($costToday / $dailyBudget * 100) . "%", ['telegram', 'dashboard']);
            }

            // Classify
            $state = $number->circuit_breaker_state ?? 'open';
            if ($state === 'open' && $blockRate < 0.05) {
                $totalHealthy++;
            } elseif (in_array($state, ['pause', 'stop'])) {
                $totalPaused++;
            } else {
                $totalDegraded++;
            }

            $this->line("  {$number->phone_number}: sent={$sent}, blocked={$blocked}, rate=" . round($blockRate * 100, 2) . "%, health={$healthScore}, state={$state}");
        }

        // Global health log
        WhatsAppHealthLog::create([
            'quality_rating' => $totalPaused > 0 ? 'red' : ($totalDegraded > 0 ? 'yellow' : 'green'),
            'tier' => 'pool',
            'sent_24h' => $numbers->sum(fn ($n) => (int) Redis::get("wa_num:{$n->id}:sent_today")),
            'delivered_24h' => 0,
            'blocked_24h' => $numbers->sum(fn ($n) => (int) Redis::get("wa_num:{$n->id}:blocked_24h")),
            'block_rate' => 0,
            'circuit_breaker_state' => $totalPaused > 0 ? 'pause' : ($totalDegraded > 0 ? 'reduce' : 'open'),
            'warmup_week' => 0,
            'daily_limit' => 0,
            'budget_spent_cents' => 0,
            'checked_at' => now(),
        ]);

        // Alert if critical
        if ($totalPaused > 0) {
            $notifier->alert('critical', 'whatsapp_pool', "{$totalPaused} WhatsApp number(s) paused/stopped. Pool: {$totalHealthy} healthy, {$totalDegraded} degraded.", ['telegram', 'email', 'dashboard']);
        }

        $this->info("WhatsApp monitoring complete: {$totalHealthy} healthy, {$totalDegraded} degraded, {$totalPaused} paused");
        Log::info('MonitorWhatsAppQuality completed', compact('totalHealthy', 'totalDegraded', 'totalPaused'));

        return Command::SUCCESS;
    }
}
