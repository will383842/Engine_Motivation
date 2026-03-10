<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LifecycleService;
use Illuminate\Console\Command;

class ProcessLifecycleTransitions extends Command
{
    protected $signature = 'lifecycle:process';
    protected $description = 'Evaluate chatters for lifecycle state transitions';

    public function handle(LifecycleService $lifecycleService): int
    {
        $this->info('Starting ProcessLifecycleTransitions...');
        $lifecycleService->processAll();
        $this->info('ProcessLifecycleTransitions completed.');
        return Command::SUCCESS;
    }
}
