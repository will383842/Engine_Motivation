<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Chatter;
use App\Models\RevenueAttribution;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RevenueAttributionWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $revenueThisMonth = (int) RevenueAttribution::where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('commission_cents');

        $activeChatters = Chatter::where('lifecycle_state', 'active')
            ->where('is_active', true)
            ->count();

        $arpc = $activeChatters > 0
            ? round($revenueThisMonth / $activeChatters / 100, 2)
            : 0;

        return [
            Stat::make('Revenue This Month', '$' . number_format($revenueThisMonth / 100, 2))
                ->description(Carbon::now()->format('F Y'))
                ->color('success'),
            Stat::make('Revenue Per Chatter', '$' . number_format($arpc, 2))
                ->description('ARPC — ' . $activeChatters . ' active chatters')
                ->color('primary'),
        ];
    }
}
