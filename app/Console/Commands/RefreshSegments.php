<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Segment;
use App\Services\SegmentResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshSegments extends Command
{
    protected $signature = 'segments:refresh
                            {--segment= : Refresh a specific segment by ID}
                            {--force : Bypass cache and force recalculation}';

    protected $description = 'Refresh all dynamic segments by recalculating their cached member counts';

    public function handle(SegmentResolver $segmentResolver): int
    {
        $this->info('Starting segments:refresh...');
        $startTime = microtime(true);

        $specificId = $this->option('segment');
        $force = (bool) $this->option('force');

        $query = Segment::where('is_dynamic', true);

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $segments = $query->get();

        if ($segments->isEmpty()) {
            $this->warn('No dynamic segments found to refresh.');
            return Command::SUCCESS;
        }

        $refreshed = 0;
        $errors = 0;

        /** @var Segment $segment */
        foreach ($segments as $segment) {
            try {
                if ($force) {
                    Cache::forget("segment:{$segment->id}:count");
                }

                $members = $segmentResolver->resolve($segment);
                $count = $members->count();

                $refreshed++;
                $this->line("  [{$segment->name}] -> {$count} member(s)");
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  [{$segment->name}] FAILED: {$e->getMessage()}");
                Log::error('segments:refresh failed for segment', [
                    'segment_id' => $segment->id,
                    'segment_name' => $segment->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("Refreshed {$refreshed} segment(s) in {$elapsed}s." . ($errors > 0 ? " ({$errors} error(s))" : ''));

        Log::info('segments:refresh completed', [
            'refreshed' => $refreshed,
            'errors' => $errors,
            'elapsed_seconds' => $elapsed,
        ]);

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
