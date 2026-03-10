<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MessageLinkClick;
use App\Models\MessageLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    public function click(Request $request, string $messageLogId, string $linkHash): RedirectResponse
    {
        // Try cache first, then fallback to DB
        $cacheKey = "tracking:{$messageLogId}:{$linkHash}";
        $data = Cache::get($cacheKey);

        if (!$data) {
            // Fallback: look up the message log and reconstruct tracking data
            $messageLog = MessageLog::find($messageLogId);
            if (!$messageLog) {
                abort(404);
            }

            $data = [
                'message_log_id' => $messageLog->id,
                'chatter_id' => $messageLog->chatter_id,
                'url' => $messageLog->metadata['tracked_urls'][$linkHash] ?? null,
            ];

            if (!$data['url']) {
                abort(404);
            }
        }

        MessageLinkClick::create([
            'message_log_id' => $data['message_log_id'],
            'chatter_id' => $data['chatter_id'],
            'url' => $data['url'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Update click stats on the message log
        DB::table('message_logs')
            ->where('id', $data['message_log_id'])
            ->update([
                'clicked_at' => DB::raw('COALESCE(clicked_at, NOW())'),
                'click_count' => DB::raw('click_count + 1'),
            ]);

        return redirect($data['url']);
    }
}
