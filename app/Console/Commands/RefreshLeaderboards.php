<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LeaderboardService;
use Illuminate\Console\Command;

class RefreshLeaderboards extends Command
{
    protected $signature = 'leaderboards:refresh';
    protected $description = 'Recalculate all leaderboard rankings';

    public function handle(LeaderboardService $leaderboardService): int
    {
        $this->info('Starting RefreshLeaderboards...');
        $leaderboardService->persistToDatabase();
        $this->info('RefreshLeaderboards completed.');
        return Command::SUCCESS;
    }
}
