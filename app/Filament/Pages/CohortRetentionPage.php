<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Chatter;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class CohortRetentionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.pages.cohort-retention';
    protected static ?string $title = 'Cohort Retention';
    protected static ?string $navigationGroup = 'Analytics';

    protected function getViewData(): array
    {
        $periods = [7, 14, 30, 60, 90];
        $startDate = now()->subMonths(6)->startOfMonth();

        // Get all chatters from the last 6 months in one query
        $chatters = Chatter::query()
            ->where('created_at', '>=', $startDate)
            ->select('id', 'created_at', 'last_active_at')
            ->get();

        // Group by registration month in PHP (DB-agnostic)
        $grouped = $chatters->groupBy(fn ($c) => $c->created_at->format('Y-m'));

        $retentionData = $grouped->sortKeys()->map(function ($cohortChatters, $month) use ($periods) {
            $total = $cohortChatters->count();
            $cohortStart = Carbon::parse($month . '-01')->startOfMonth();

            $retention = [];
            foreach ($periods as $days) {
                $checkDate = $cohortStart->copy()->addDays($days);

                if ($checkDate->isFuture()) {
                    $retention[$days] = null;
                    continue;
                }

                $activeCount = $cohortChatters->filter(
                    fn ($c) => $c->last_active_at !== null && $c->last_active_at->gte($checkDate)
                )->count();

                $retention[$days] = $total > 0 ? round(($activeCount / $total) * 100, 1) : 0;
            }

            return [
                'month' => $month,
                'total' => $total,
                'retention' => $retention,
            ];
        })->values();

        return [
            'retentionData' => $retentionData,
            'periods' => $periods,
        ];
    }
}
