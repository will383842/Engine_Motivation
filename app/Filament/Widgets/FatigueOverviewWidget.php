<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ChatterFatigueScore;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class FatigueOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Fatigue Overview';

    protected function getData(): array
    {
        $results = ChatterFatigueScore::select(DB::raw("
            CASE
                WHEN fatigue_score BETWEEN 0 AND 20 THEN '0-20'
                WHEN fatigue_score BETWEEN 21 AND 40 THEN '21-40'
                WHEN fatigue_score BETWEEN 41 AND 60 THEN '41-60'
                WHEN fatigue_score BETWEEN 61 AND 80 THEN '61-80'
                ELSE '81-100'
            END as fatigue_range
        "), DB::raw('COUNT(*) as total'))
            ->groupBy('fatigue_range')
            ->pluck('total', 'fatigue_range');

        $ranges = ['0-20', '21-40', '41-60', '61-80', '81-100'];
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
                        '#22c55e', '#84cc16', '#eab308', '#f97316', '#ef4444',
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
