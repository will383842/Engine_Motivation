<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SuppressionList;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuppressionListWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $active = SuppressionList::whereNull('lifted_at');

        $totalSuppressed = (clone $active)->count();

        $optOuts = (clone $active)->where('reason', 'opt_out')->count();

        $blocked = (clone $active)->where('reason', 'blocked')->count();

        $gdprErasures = (clone $active)->where('reason', 'gdpr_erasure')->count();

        return [
            Stat::make('Total Suppressed', number_format($totalSuppressed))
                ->description('Active suppressions')
                ->color('danger'),
            Stat::make('Opt-Outs', number_format($optOuts))
                ->description('User-initiated')
                ->color('warning'),
            Stat::make('Blocked', number_format($blocked))
                ->description('Carrier / platform blocks')
                ->color('danger'),
            Stat::make('GDPR Erasures', number_format($gdprErasures))
                ->description('Right to erasure requests')
                ->color('gray'),
        ];
    }
}
