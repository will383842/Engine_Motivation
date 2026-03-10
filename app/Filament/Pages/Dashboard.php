<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ActiveChattersWidget;
use App\Filament\Widgets\ChurnPredictionWidget;
use App\Filament\Widgets\ConversionFunnelWidget;
use App\Filament\Widgets\FatigueOverviewWidget;
use App\Filament\Widgets\FraudAlertsWidget;
use App\Filament\Widgets\HealthScoreWidget;
use App\Filament\Widgets\LifecycleFunnelWidget;
use App\Filament\Widgets\MessageCostWidget;
use App\Filament\Widgets\MessagingRoiWidget;
use App\Filament\Widgets\RevenueAttributionWidget;
use App\Filament\Widgets\StreakDistributionWidget;
use App\Filament\Widgets\SuppressionListWidget;
use App\Filament\Widgets\WhatsAppPoolWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?int $navigationSort = -2;

    public function getHeaderWidgets(): array
    {
        return [
            ActiveChattersWidget::class,
            MessageCostWidget::class,
            HealthScoreWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            ConversionFunnelWidget::class,
            StreakDistributionWidget::class,
            LifecycleFunnelWidget::class,
            MessagingRoiWidget::class,
            RevenueAttributionWidget::class,
            FatigueOverviewWidget::class,
            ChurnPredictionWidget::class,
            WhatsAppPoolWidget::class,
            SuppressionListWidget::class,
            FraudAlertsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 2;
    }
}
