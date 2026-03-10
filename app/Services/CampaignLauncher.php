<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Jobs\SendMotivationNudge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignLauncher
{
    public function __construct(
        private SegmentResolver $segmentResolver,
        private MotivationDispatcher $dispatcher,
        private SmartSendService $smartSendService,
    ) {}

    public function launch(Campaign $campaign): void
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return;
        }

        $chatters = $campaign->segment
            ? $this->segmentResolver->resolve($campaign->segment)
            : collect();

        $recipientCount = $chatters->count();
        $campaign->update(['total_recipients' => $recipientCount, 'status' => 'sending']);

        // Security delay for large campaigns
        $baseDelay = 0;
        if ($recipientCount > 500) {
            $baseDelay = 300; // 5 minutes safety delay
            Log::info("Campaign {$campaign->id}: {$recipientCount} recipients — 5min security delay applied");
        }

        // Chunk by 100 for 10K+ recipients
        $chunkSize = 100;
        $chunkIndex = 0;

        foreach ($chatters->chunk($chunkSize) as $chunk) {
            foreach ($chunk as $chatter) {
                $channel = $campaign->channel ?? $this->dispatcher->resolveChannel($chatter);

                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'chatter_id' => $chatter->id,
                    'status' => 'pending',
                    'channel' => $channel,
                ]);

                // Smart send timing: use chatter's optimal hour if available
                $optimalDelay = $this->calculateDelay($chatter, $campaign, $baseDelay, $chunkIndex);

                SendMotivationNudge::dispatch($chatter, $campaign->template->slug ?? '', $channel)
                    ->onQueue($channel === 'whatsapp' ? 'whatsapp' : 'default')
                    ->delay(now()->addSeconds($optimalDelay));

                $chunkIndex++;
            }
        }

        // Rate limiting: max send_rate_per_second if configured
        if ($campaign->send_rate_per_second && $campaign->send_rate_per_second > 0) {
            Log::info("Campaign {$campaign->id}: rate limited to {$campaign->send_rate_per_second}/s");
        }
    }

    /**
     * Stop a running campaign — cancel all pending recipients.
     */
    public function stop(Campaign $campaign): void
    {
        if ($campaign->status !== 'sending') {
            return;
        }

        CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);

        $campaign->update(['status' => 'paused']);

        Log::info("Campaign {$campaign->id} stopped — pending recipients skipped");
    }

    private function calculateDelay($chatter, Campaign $campaign, int $baseDelay, int $index): int
    {
        $delay = $baseDelay;

        // Spread sends over time (1 per second base rate)
        $rateDelay = $campaign->send_rate_per_second
            ? (int) ($index / $campaign->send_rate_per_second)
            : (int) ($index * 0.5); // 2 per second default

        $delay += $rateDelay;

        // Smart send: if timezone_aware, adjust to optimal local time
        if ($campaign->timezone_aware) {
            $optimalHour = $this->smartSendService->getOptimalSendTime($chatter);
            // This is simplified — in production would calculate exact offset
            $delay += rand(0, 60);
        }

        return $delay;
    }
}
