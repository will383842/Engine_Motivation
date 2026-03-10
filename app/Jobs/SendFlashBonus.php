<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\CampaignLauncher;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFlashBonus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'high';
    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(public readonly Campaign $campaign) {}

    public function handle(CampaignLauncher $campaignLauncher): void
    {
        $campaignLauncher->launch($this->campaign);
    }
}
