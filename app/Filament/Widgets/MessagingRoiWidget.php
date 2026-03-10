<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\MessageLog;
use App\Models\RevenueAttribution;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class MessagingRoiWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $totalCostCents = (int) MessageLog::where('sent_at', '>=', $startOfMonth)
            ->sum('cost_cents');

        $revenueCents = (int) RevenueAttribution::where('created_at', '>=', $startOfMonth)
            ->sum('commission_cents');

        $roi = $totalCostCents > 0
            ? round((($revenueCents - $totalCostCents) / $totalCostCents) * 100, 1)
            : 0;

        $roiColor = match (true) {
            $roi > 200 => 'success',
            $roi > 0 => 'warning',
            default => 'danger',
        };

        return [
            Stat::make('Messaging Cost (Month)', '$' . number_format($totalCostCents / 100, 2))
                ->description('All channels')
                ->color('warning'),
            Stat::make('Revenue Attributed', '$' . number_format($revenueCents / 100, 2))
                ->description(Carbon::now()->format('F Y'))
                ->color('success'),
            Stat::make('ROI', $roi . '%')
                ->description('(Revenue - Cost) / Cost')
                ->color($roiColor),
        ];
    }
}
