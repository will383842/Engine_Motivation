<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SequenceEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdvanceSequences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'default';
    public $tries = 3;
    public $backoff = [30, 60, 120];

    

    public function handle(SequenceEngine $sequenceEngine): void
    {
        $sequenceEngine->advanceAll();
    }
}
