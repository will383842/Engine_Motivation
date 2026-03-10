<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\WhatsAppHealthLog;
use App\Models\WhatsAppNumber;
use Filament\Pages\Page;

class WhatsAppHealthPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static string $view = 'filament.pages.whats-app-health';
    protected static ?string $title = 'WhatsApp Pool Health';
    protected static ?string $navigationGroup = 'Monitoring';

    protected function getViewData(): array
    {
        $numbers = WhatsAppNumber::query()
            ->orderByDesc('is_active')
            ->orderByDesc('health_score')
            ->get();

        $totalActive = $numbers->where('is_active', true)->count();
        $totalInactive = $numbers->where('is_active', false)->count();
        $avgHealthScore = $numbers->where('is_active', true)->avg('health_score') ?? 0;
        $openCircuitBreakers = $numbers->where('circuit_breaker_state', 'open')->count();

        $totalSent = $numbers->sum('total_sent');
        $totalDelivered = $numbers->sum('total_delivered');
        $totalBlocked = $numbers->sum('total_blocked');
        $totalBudgetCents = $numbers->sum('total_cost_cents');

        // Recent health logs (last 24h)
        $recentLogs = WhatsAppHealthLog::query()
            ->where('checked_at', '>=', now()->subDay())
            ->orderByDesc('checked_at')
            ->limit(20)
            ->get();

        return [
            'numbers' => $numbers,
            'totalActive' => $totalActive,
            'totalInactive' => $totalInactive,
            'avgHealthScore' => round($avgHealthScore, 1),
            'openCircuitBreakers' => $openCircuitBreakers,
            'totalSent' => $totalSent,
            'totalDelivered' => $totalDelivered,
            'totalBlocked' => $totalBlocked,
            'totalBudgetCents' => $totalBudgetCents,
            'recentLogs' => $recentLogs,
        ];
    }
}
