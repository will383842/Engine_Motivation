<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\SaleCompleted;
use App\Services\LeaderboardService;
use Illuminate\Contracts\Queue\ShouldQueue;
class UpdateLeaderboard implements ShouldQueue {
    public $queue = "high";
    public function __construct(private readonly LeaderboardService $service) {}
    public function handle(SaleCompleted $event): void {
        $this->service->updateScore($event->chatter, $event->commissionCents);
    }
}