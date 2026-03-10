<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\LifecycleTransition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
class LogLifecycleTransition implements ShouldQueue {
    public $queue = "default";
    public function handle(LifecycleTransition $event): void {
        Log::info("Lifecycle transition", ["chatter" => $event->chatter->id, "from" => $event->fromState, "to" => $event->toState]);
    }
}