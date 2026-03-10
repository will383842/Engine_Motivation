<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\LeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateLeaderboard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'low';
    public $tries = 3;
    public $backoff = [30, 60, 120];

    

    public function handle(LeaderboardService $leaderboardService): void
    {
        $leaderboardService->persistToDatabase(); $leaderboardService->refreshLeaderboard();
    }
}
