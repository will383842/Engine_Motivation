<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EngagementScoreService;
use Illuminate\Console\Command;

class CalculateEngagementScores extends Command
{
    protected $signature = 'engagement:calculate';
    protected $description = 'Recalculate engagement scores for all chatters';

    public function handle(EngagementScoreService $engagementScoreService): int
    {
        $this->info('Starting CalculateEngagementScores...');
        $engagementScoreService->recalculateAll();
        $this->info('CalculateEngagementScores completed.');
        return Command::SUCCESS;
    }
}
