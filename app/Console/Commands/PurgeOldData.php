<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeOldData extends Command
{
    protected $signature = 'data:purge {--dry-run}';
    protected $description = 'Delete old data per retention policy';

    /** Retention periods in days */
    private const RETENTION = [
        'message_logs' => 90,
        'webhook_events' => 30,
        'activity_log' => 180,
        'chatter_events' => 365,
        'sender_health_logs' => 60,
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info('Starting PurgeOldData' . ($dryRun ? ' (DRY RUN)' : '') . '...');

        $totalDeleted = 0;

        foreach (self::RETENTION as $table => $days) {
            $dateColumn = $table === 'activity_log' ? 'created_at' : 'created_at';
            $cutoff = now()->subDays($days)->toDateTimeString();

            $count = DB::table($table)->where($dateColumn, '<', $cutoff)->count();

            if ($dryRun) {
                $this->line("  [DRY RUN] {$table}: {$count} rows older than {$days} days");
            } else {
                // Delete in chunks to avoid lock contention on partitioned tables
                $deleted = 0;
                do {
                    $batch = DB::table($table)
                        ->where($dateColumn, '<', $cutoff)
                        ->limit(5000)
                        ->delete();
                    $deleted += $batch;
                } while ($batch > 0);

                $this->line("  {$table}: deleted {$deleted} rows (retention: {$days} days)");
                $totalDeleted += $deleted;
            }
        }

        Log::info('PurgeOldData completed', ['total_deleted' => $totalDeleted, 'dry_run' => $dryRun]);
        $this->info("PurgeOldData completed. Total deleted: {$totalDeleted}");

        return Command::SUCCESS;
    }
}
