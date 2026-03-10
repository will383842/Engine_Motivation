<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\MotivationDispatcher;
use App\Services\AdminNotifier;
use App\Models\Chatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SendMotivationNudge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'default';
    public $tries = 3;
    public $backoff = [30, 120, 600];

    public function __construct(
        public readonly Chatter $chatter,
        public readonly string $templateSlug,
        public readonly ?string $channel = null,
    ) {}

    public function handle(MotivationDispatcher $motivationDispatcher): void
    {
        $motivationDispatcher->send($this->chatter, $this->templateSlug, $this->channel);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendMotivationNudge failed for chatter {$this->chatter->id}: {$exception->getMessage()}");

        $failsKey = 'nudge_fails_1h';
        $fails = (int) Redis::incr($failsKey);
        Redis::expire($failsKey, 3600);

        if ($fails > 50) {
            app(AdminNotifier::class)->alert(
                'critical',
                'messaging',
                "More than 50 SendMotivationNudge failures in the last hour",
                ['dashboard', 'telegram', 'email']
            );
        }
    }
}
