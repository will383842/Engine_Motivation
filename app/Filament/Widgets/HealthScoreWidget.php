<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ChatterEngagementScore;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HealthScoreWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $avgScore = round((float) ChatterEngagementScore::avg('engagement_score'), 1);

        $color = match (true) {
            $avgScore > 70 => 'success',
            $avgScore >= 40 => 'warning',
            default => 'danger',
        };

        $label = match (true) {
            $avgScore > 70 => 'Healthy',
            $avgScore >= 40 => 'Needs attention',
            default => 'Critical',
        };

        return [
            Stat::make('Global Health Score', $avgScore . ' / 100')
                ->description($label)
                ->color($color),
        ];
    }
}
