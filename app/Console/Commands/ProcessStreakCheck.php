<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Chatter;
use App\Services\StreakService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessStreakCheck extends Command
{
    protected $signature = 'streaks:check {--dry-run : Show what would happen without making changes}';
    protected $description = 'Check all chatters for streak maintenance (batch by timezone, DST-aware)';

    public function handle(StreakService $streakService): int
    {
        $dryRun = $this->option('dry-run');
        $this->info('Starting streak check' . ($dryRun ? ' (DRY RUN)' : '') . '...');

        // Group chatters by timezone for DST-correct "day" boundaries
        $timezones = Chatter::query()
            ->where('is_active', true)
            ->whereHas('streak', fn ($q) => $q->where('current_count', '>', 0))
            ->select('timezone')
            ->distinct()
            ->pluck('timezone')
            ->filter()
            ->push('UTC') // fallback timezone
            ->unique();

        $totalChecked = 0;
        $totalBroken = 0;
        $totalFrozen = 0;

        foreach ($timezones as $tz) {
            try {
                $chatters = Chatter::query()
                    ->where('is_active', true)
                    ->where(fn ($q) => $q->where('timezone', $tz)->orWhere(function ($q) use ($tz) {
                        if ($tz === 'UTC') {
                            $q->whereNull('timezone');
                        }
                    }))
                    ->whereHas('streak', fn ($q) => $q->where('current_count', '>', 0))
                    ->with('streak')
                    ->cursor();

                foreach ($chatters as $chatter) {
                    $totalChecked++;

                    if ($dryRun) {
                        $streak = $chatter->streak;
                        if ($streak && $streak->current_count > 0) {
                            $this->line("  [{$tz}] {$chatter->display_name}: streak={$streak->current_count}, last={$streak->last_activity_date}");
                        }
                        continue;
                    }

                    try {
                        $beforeCount = $chatter->streak->current_count;
                        $streakService->checkAndBreakInactive($chatter);
                        $chatter->streak->refresh();

                        if ($chatter->streak->current_count === 0 && $beforeCount > 0) {
                            $totalBroken++;
                        } elseif ($chatter->streak->streak_frozen_until) {
                            $totalFrozen++;
                        }
                    } catch (\Throwable $e) {
                        Log::error("Streak check failed for chatter {$chatter->id}: {$e->getMessage()}");
                        $this->error("  Failed: {$chatter->id} — {$e->getMessage()}");
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Streak check failed for timezone {$tz}: {$e->getMessage()}");
                $this->error("Failed processing timezone {$tz}: {$e->getMessage()}");
            }
        }

        $this->info("Streak check completed: {$totalChecked} checked, {$totalBroken} broken, {$totalFrozen} frozen");
        Log::info('ProcessStreakCheck completed', compact('totalChecked', 'totalBroken', 'totalFrozen'));

        return Command::SUCCESS;
    }
}
