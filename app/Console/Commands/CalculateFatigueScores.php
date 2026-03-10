<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FatigueScoreService;
use Illuminate\Console\Command;

class CalculateFatigueScores extends Command
{
    protected $signature = 'fatigue:calculate';
    protected $description = 'Recalculate fatigue scores for all chatters';

    public function handle(FatigueScoreService $fatigueScoreService): int
    {
        $this->info('Starting CalculateFatigueScores...');
        $fatigueScoreService->recalculateAll();
        $this->info('CalculateFatigueScores completed.');
        return Command::SUCCESS;
    }
}
