<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\ChatterInteracted;
use App\Services\EngagementScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
class UpdateEngagementScore implements ShouldQueue {
    public $queue = "low";
    public function __construct(private readonly EngagementScoreService $service) {}
    public function handle(ChatterInteracted $event): void {
        $this->service->calculate($event->chatter);
    }
}