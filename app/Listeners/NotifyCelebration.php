<?php
declare(strict_types=1);
namespace App\Listeners;
use App\Events\BadgeEarned;
use App\Services\MotivationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
class NotifyCelebration implements ShouldQueue {
    public $queue = "high";
    public function __construct(private readonly MotivationDispatcher $dispatcher) {}
    public function handle(BadgeEarned $event): void {
        $this->dispatcher->send($event->chatter, "badge_earned_celebration", null, ["badge_name" => $event->badge->names["en"] ?? $event->badge->slug]);
    }
}