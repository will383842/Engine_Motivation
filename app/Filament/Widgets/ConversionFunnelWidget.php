<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Chatter;
use Filament\Widgets\ChartWidget;

class ConversionFunnelWidget extends ChartWidget
{
    protected static ?string $heading = 'Conversion Funnel';

    protected function getData(): array
    {
        $registered = Chatter::count();

        $onboarding = Chatter::whereIn('lifecycle_state', [
            'onboarding', 'active', 'declining', 'dormant', 'churned', 'sunset',
        ])->count();

        $active = Chatter::where('lifecycle_state', 'active')
            ->where('is_active', true)
            ->count();

        $firstSale = Chatter::where('total_sales', '>=', 1)->count();

        $recurring = Chatter::where('total_sales', '>=', 3)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Chatters',
                    'data' => [$registered, $onboarding, $active, $firstSale, $recurring],
                    'backgroundColor' => [
                        '#94a3b8', '#60a5fa', '#34d399', '#fbbf24', '#f87171',
                    ],
                ],
            ],
            'labels' => ['Registered', 'Onboarding+', 'Active', 'First Sale', 'Recurring (3+)'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
