<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CampaignComparisonPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.campaign-comparison';
    protected static ?string $title = 'Campaign Comparison';
    protected static ?string $navigationGroup = 'Campaigns';

    protected function getViewData(): array
    {
        // Get the 5 most recent completed campaigns
        $campaigns = Campaign::query()
            ->whereIn('status', ['sent', 'sending'])
            ->where('total_recipients', '>', 0)
            ->latest('scheduled_at')
            ->limit(5)
            ->get();

        $comparisons = $campaigns->map(function (Campaign $campaign) {
            $recipients = CampaignRecipient::where('campaign_id', $campaign->id);

            $sent = (clone $recipients)->whereNotNull('sent_at')->count();
            $delivered = (clone $recipients)->whereNotNull('delivered_at')->count();
            $failed = (clone $recipients)->whereNotNull('failed_at')->count();

            // Read count from message_logs joined via external_msg_id
            $readCount = \App\Models\MessageLog::query()
                ->where('source_type', 'campaign')
                ->where('source_id', $campaign->id)
                ->whereNotNull('read_at')
                ->count();

            $deliveryRate = $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0;
            $readRate = $delivered > 0 ? round(($readCount / $delivered) * 100, 1) : 0;
            $failRate = $sent > 0 ? round(($failed / $sent) * 100, 1) : 0;

            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'channel' => $campaign->channel,
                'status' => $campaign->status,
                'scheduled_at' => $campaign->scheduled_at?->format('Y-m-d H:i'),
                'total_recipients' => $campaign->total_recipients,
                'sent' => $sent,
                'delivered' => $delivered,
                'read' => $readCount,
                'failed' => $failed,
                'delivery_rate' => $deliveryRate,
                'read_rate' => $readRate,
                'fail_rate' => $failRate,
            ];
        });

        return [
            'comparisons' => $comparisons,
        ];
    }
}
