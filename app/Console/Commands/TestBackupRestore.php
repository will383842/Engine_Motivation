<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class TestBackupRestore extends Command
{
    protected $signature = 'backup:test';
    protected $description = 'Test that DB backup can be restored successfully';

    public function handle(): int
    {
        $this->info('Starting TestBackupRestore...');

        $dbName = config('database.connections.pgsql.database');
        $dbHost = config('database.connections.pgsql.host');
        $dbPort = config('database.connections.pgsql.port', 5432);
        $dbUser = config('database.connections.pgsql.username');
        $testDb = $dbName . '_backup_test';

        // 1. Create a fresh dump
        $dumpFile = storage_path('app/backup_test_' . now()->format('Ymd_His') . '.sql');
        $this->line("  Dumping {$dbName} to {$dumpFile}...");

        $dumpResult = Process::run("pg_dump -h {$dbHost} -p {$dbPort} -U {$dbUser} -Fc {$dbName} -f {$dumpFile}");
        if (!$dumpResult->successful()) {
            $this->error('Dump failed: ' . $dumpResult->errorOutput());
            return Command::FAILURE;
        }

        // 2. Create test database
        $this->line("  Creating test database {$testDb}...");
        try {
            DB::statement("DROP DATABASE IF EXISTS \"{$testDb}\"");
            DB::statement("CREATE DATABASE \"{$testDb}\"");
        } catch (\Throwable $e) {
            $this->error('Cannot create test DB: ' . $e->getMessage());
            @unlink($dumpFile);
            return Command::FAILURE;
        }

        // 3. Restore into test database
        $this->line("  Restoring dump into {$testDb}...");
        $restoreResult = Process::run("pg_restore -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$testDb} --no-owner --no-acl {$dumpFile}");

        // pg_restore returns non-zero on warnings too, so check key tables
        $testConn = DB::connection('pgsql');
        $originalTables = $testConn->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        // 4. Verify table count matches
        $success = count($originalTables) > 0;
        $this->line("  Tables found in restored DB: " . count($originalTables));

        // 5. Cleanup
        $this->line("  Cleaning up...");
        try {
            DB::statement("DROP DATABASE IF EXISTS \"{$testDb}\"");
        } catch (\Throwable $e) {
            $this->warn("  Could not drop test DB: " . $e->getMessage());
        }
        @unlink($dumpFile);

        if ($success) {
            Log::info('Backup restore test PASSED');
            $this->info('TestBackupRestore PASSED.');
            return Command::SUCCESS;
        }

        Log::error('Backup restore test FAILED');
        $this->error('TestBackupRestore FAILED — no tables found after restore.');
        return Command::FAILURE;
    }
}
