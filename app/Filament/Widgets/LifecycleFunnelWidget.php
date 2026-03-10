<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Chatter;
use App\Enums\LifecycleState;
use Filament\Widgets\ChartWidget;

class LifecycleFunnelWidget extends ChartWidget
{
    protected static ?string $heading = 'Lifecycle Funnel';

    protected function getData(): array
    {
        $states = LifecycleState::cases();
        $labels = [];
        $counts = [];
        $colors = [
            'registered' => '#94a3b8',
            'onboarding' => '#60a5fa',
            'active' => '#22c55e',
            'declining' => '#f97316',
            'dormant' => '#eab308',
            'churned' => '#ef4444',
            'sunset' => '#6b7280',
        ];
        $bgColors = [];

        foreach ($states as $state) {
            $labels[] = $state->label();
            $counts[] = Chatter::where('lifecycle_state', $state->value)->count();
            $bgColors[] = $colors[$state->value] ?? '#94a3b8';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Chatters',
                    'data' => $counts,
                    'backgroundColor' => $bgColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
