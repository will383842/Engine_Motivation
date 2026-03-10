<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\ChatterInteracted;
use App\Services\SmartSendService;
use Illuminate\Contracts\Queue\ShouldQueue;
class UpdateSendTimeProfile implements ShouldQueue {
    public $queue = "high";
    public function __construct(private readonly SmartSendService $service) {}
    public function handle(ChatterInteracted $event): void {
        $this->service->updateProfile($event->chatter, now());
    }
}