<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SegmentResolver;
use App\Models\Segment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshSegments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'low';
    public $tries = 3;
    public $backoff = [30, 60, 120];

    

    public function handle(SegmentResolver $segmentResolver): void
    {
        Segment::where("is_dynamic", true)->each(fn($s) => $segmentResolver->resolve($s));
    }
}
