<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Chatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StreakDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Streak Distribution';

    protected function getData(): array
    {
        $results = Chatter::select(DB::raw("
            CASE
                WHEN current_streak = 0 THEN '0'
                WHEN current_streak BETWEEN 1 AND 3 THEN '1-3'
                WHEN current_streak BETWEEN 4 AND 7 THEN '4-7'
                WHEN current_streak BETWEEN 8 AND 14 THEN '8-14'
                WHEN current_streak BETWEEN 15 AND 30 THEN '15-30'
                WHEN current_streak BETWEEN 31 AND 90 THEN '31-90'
                ELSE '91+'
            END as streak_range
        "), DB::raw('COUNT(*) as total'))
            ->groupBy('streak_range')
            ->pluck('total', 'streak_range');

        $ranges = ['0', '1-3', '4-7', '8-14', '15-30', '31-90', '91+'];
        $data = [];
        foreach ($ranges as $range) {
            $data[] = (int) ($results[$range] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Chatters',
                    'data' => $data,
                    'backgroundColor' => [
                        '#ef4444', '#f97316', '#eab308',
                        '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6',
                    ],
                ],
            ],
            'labels' => $ranges,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
