<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\SaleCompleted;
use App\Services\MissionService;
use Illuminate\Contracts\Queue\ShouldQueue;
class CheckMissionProgress implements ShouldQueue {
    public $queue = "high";
    public function __construct(private readonly MissionService $service) {}
    public function handle(SaleCompleted $event): void {
        $this->service->incrementProgress($event->chatter, "sale_completed");
    }
}