<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\WebhookReceived;
use App\Services\EventProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
class TriggerSequences implements ShouldQueue {
    public $queue = "webhooks";
    public function __construct(private readonly EventProcessor $processor) {}
    public function handle(WebhookReceived $event): void {
        $this->processor->process($event->webhookEvent);
    }
}