<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\FraudFlag;
use Filament\Widgets\Widget;

class FraudAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.FraudAlertsWidget';
    protected static ?string $heading = 'Latest Fraud Alerts';

    protected function getViewData(): array
    {
        $alerts = FraudFlag::with('chatter:id,display_name,email')
            ->where('resolved', false)
            ->where('severity', 'high')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'alerts' => $alerts,
        ];
    }
}
