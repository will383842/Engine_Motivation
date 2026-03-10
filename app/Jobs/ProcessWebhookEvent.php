<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\EventProcessor;
use App\Services\AdminNotifier;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'webhooks';
    public $tries = 5;
    public $backoff = [30, 60, 120, 300, 600];

    public function __construct(public readonly WebhookEvent $webhookEvent) {}

    public function handle(EventProcessor $eventProcessor): void
    {
        $eventProcessor->process($this->webhookEvent);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessWebhookEvent permanently failed: {$exception->getMessage()}", [
            'event_id' => $this->webhookEvent->id,
            'event_type' => $this->webhookEvent->event_type,
        ]);

        $this->webhookEvent->update(['status' => 'failed']);

        app(AdminNotifier::class)->alert(
            'warning',
            'webhooks',
            "Webhook processing failed after {$this->tries} attempts: {$this->webhookEvent->event_type} (ID: {$this->webhookEvent->id})",
            ['dashboard', 'telegram']
        );
    }
}
