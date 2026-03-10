<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class CreatePartitions extends Command
{
    protected $signature = 'partitions:create {--month=}';
    protected $description = 'Create next month table partitions';

    public function handle(DB $dB): int
    {
        $this->info('Starting CreatePartitions...');
        DB::select("SELECT create_next_month_partitions()");
        $this->info('CreatePartitions completed.');
        return Command::SUCCESS;
    }
}
