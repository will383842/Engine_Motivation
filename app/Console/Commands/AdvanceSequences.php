<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SequenceEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AdvanceSequences extends Command
{
    protected $signature = 'sequences:advance';
    protected $description = 'Advance all active sequence enrollments that are due for their next step';

    public function handle(SequenceEngine $sequenceEngine): int
    {
        $this->info('Starting sequences:advance...');

        $startTime = microtime(true);

        try {
            $processed = $sequenceEngine->advanceAll();
        } catch (\Throwable $e) {
            $this->error("Failed to advance sequences: {$e->getMessage()}");
            Log::error('sequences:advance failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info("Processed {$processed} sequence enrollment(s) in {$elapsed}s.");
        Log::info('sequences:advance completed', [
            'processed' => $processed,
            'elapsed_seconds' => $elapsed,
        ]);

        return Command::SUCCESS;
    }
}
