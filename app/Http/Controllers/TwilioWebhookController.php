<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\WhatsAppNumber;
use App\Services\WhatsAppCircuitBreaker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TwilioWebhookController extends Controller
{
    public function handleStatus(Request $request): Response
    {
        $sid = $request->input('MessageSid');
        $status = $request->input('MessageStatus');
        $from = $request->input('From');
        $errorCode = $request->input('ErrorCode');

        $log = MessageLog::where('external_msg_id', $sid)->first();
        if ($log) {
            $updates = ['status' => $status];
            match ($status) {
                'queued' => null,
                'sent' => $updates['sent_at'] = now(),
                'delivered' => $updates['delivered_at'] = now(),
                'read' => $updates['read_at'] = now(),
                'failed', 'undelivered' => $updates = array_merge($updates, [
                    'failed_at' => now(),
                    'error_code' => $errorCode,
                ]),
                default => null,
            };
            $log->update($updates);
        }

        // Update circuit breaker for the sending number
        if ($from) {
            $phoneNumber = str_replace('whatsapp:', '', $from);
            $waNumber = WhatsAppNumber::where('phone_number', $phoneNumber)->first();

            if ($waNumber) {
                $circuitBreaker = app(WhatsAppCircuitBreaker::class);

                if (in_array($status, ['delivered', 'read', 'sent'])) {
                    $circuitBreaker->recordSuccess($waNumber);
                } elseif (in_array($status, ['failed', 'undelivered'])) {
                    $circuitBreaker->recordFailure($waNumber, (int) ($errorCode ?? 0));

                    // Track blocked/undelivered counters
                    Redis::incr("wa_num:{$waNumber->id}:blocked_24h");
                    Redis::expire("wa_num:{$waNumber->id}:blocked_24h", 86400);
                }
            }
        }

        return response('', 204);
    }
}
