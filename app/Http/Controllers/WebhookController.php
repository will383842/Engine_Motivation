<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Queue;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Backpressure: reject if queue is saturated
        $queueSize = Queue::size('webhooks');
        if ($queueSize > 50000) {
            return response()->json(['error' => 'Service temporarily overloaded'], 503)
                ->header('Retry-After', '60');
        }
        if ($queueSize > 10000) {
            app(\App\Services\AdminNotifier::class)->alert(
                'warning', 'infrastructure',
                "Webhook queue size: {$queueSize} (>10K threshold)",
                ['dashboard', 'telegram']
            );
        }

        $data = $request->validate([
            'event' => 'required|string',
            'data' => 'required|array',
            'timestamp' => 'required|integer',
        ]);

        // Persist to DB FIRST (durability), then enqueue
        $event = WebhookEvent::create([
            'source' => 'firebase',
            'external_event_id' => $request->header('X-Idempotency-Key', uniqid('evt_')),
            'event_type' => $data['event'],
            'payload' => $data['data'],
            'status' => 'pending',
        ]);

        ProcessWebhookEvent::dispatch($event)->onQueue('webhooks');

        return response()->json(['status' => 'accepted', 'event_id' => $event->id], 202);
    }
}
