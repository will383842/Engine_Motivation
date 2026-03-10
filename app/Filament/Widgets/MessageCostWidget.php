<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\MessageLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class MessageCostWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $sentToday = MessageLog::whereDate('sent_at', Carbon::today())->count();

        $whatsappCostCents = (int) MessageLog::where('channel', 'whatsapp')
            ->where('sent_at', '>=', Carbon::now()->startOfMonth())
            ->sum('cost_cents');

        $totalSent = MessageLog::where('sent_at', '>=', Carbon::now()->startOfMonth())->count();
        $totalDelivered = MessageLog::where('sent_at', '>=', Carbon::now()->startOfMonth())
            ->whereNotNull('delivered_at')
            ->count();
        $deliveryRate = $totalSent > 0
            ? round(($totalDelivered / $totalSent) * 100, 1)
            : 0;

        return [
            Stat::make('Messages Sent Today', number_format($sentToday))
                ->description('All channels')
                ->color('primary'),
            Stat::make('WhatsApp Cost (Month)', '$' . number_format($whatsappCostCents / 100, 2))
                ->description(Carbon::now()->format('F Y'))
                ->color('warning'),
            Stat::make('Delivery Rate', $deliveryRate . '%')
                ->description($totalDelivered . ' / ' . $totalSent . ' this month')
                ->color($deliveryRate >= 95 ? 'success' : ($deliveryRate >= 80 ? 'warning' : 'danger')),
        ];
    }
}
