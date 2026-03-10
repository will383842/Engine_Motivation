<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WebhookCatchup extends Command
{
    protected $signature = 'webhooks:catchup
                            {--dry-run : Show what would be re-dispatched without actually dispatching}
                            {--age=2 : Minimum age in minutes for pending events (default: 2)}';

    protected $description = 'Find stale pending webhook events and re-dispatch them for processing';

    private const MAX_ATTEMPTS = 5;

    public function handle(): int
    {
        $this->info('Starting webhooks:catchup...');

        $dryRun = (bool) $this->option('dry-run');
        $ageMinutes = (int) $this->option('age');

        $cutoff = now()->subMinutes($ageMinutes);

        $staleEvents = WebhookEvent::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->where('attempts', '<', self::MAX_ATTEMPTS)
                    ->orWhereNull('attempts');
            })
            ->orderBy('created_at')
            ->get();

        if ($staleEvents->isEmpty()) {
            $this->info('No stale webhook events found. All caught up.');
            return Command::SUCCESS;
        }

        $this->info("Found {$staleEvents->count()} stale webhook event(s) older than {$ageMinutes} minute(s).");

        $dispatched = 0;
        $skipped = 0;

        foreach ($staleEvents as $event) {
            $attempts = $event->attempts ?? 0;

            if ($dryRun) {
                $this->line("  [DRY RUN] Would re-dispatch: {$event->id} ({$event->event_type}, attempts: {$attempts}, age: {$event->created_at->diffForHumans()})");
                $dispatched++;
                continue;
            }

            try {
                $event->update([
                    'attempts' => $attempts + 1,
                    'status' => 'pending',
                ]);

                ProcessWebhookEvent::dispatch($event)->onQueue('webhooks');

                $dispatched++;
                $attempt = $attempts + 1;
                $this->line("  Re-dispatched: {$event->id} ({$event->event_type}, attempt #{$attempt})");
            } catch (\Throwable $e) {
                $skipped++;
                $this->error("  Failed to re-dispatch {$event->id}: {$e->getMessage()}");
                Log::error('webhooks:catchup dispatch failed', [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Re-dispatched {$dispatched} event(s)." . ($skipped > 0 ? " Skipped {$skipped}." : ''));

        Log::info('webhooks:catchup completed', [
            'dry_run' => $dryRun,
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }
}
