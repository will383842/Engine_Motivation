<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Chatter;
use Filament\Widgets\ChartWidget;

class ChurnPredictionWidget extends ChartWidget
{
    protected static ?string $heading = 'Churn Prediction';

    protected function getData(): array
    {
        $states = ['active', 'declining', 'dormant', 'churned', 'sunset'];
        $counts = [];
        $colors = [
            'active' => '#22c55e',
            'declining' => '#f97316',
            'dormant' => '#eab308',
            'churned' => '#ef4444',
            'sunset' => '#6b7280',
        ];

        foreach ($states as $state) {
            $counts[] = Chatter::where('lifecycle_state', $state)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Chatters',
                    'data' => $counts,
                    'backgroundColor' => array_values($colors),
                ],
            ],
            'labels' => ['Active', 'Declining', 'Dormant', 'Churned', 'Sunset'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
