<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Chatter;
use App\Models\MessageLog;
use App\Models\SuppressionList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $botToken): JsonResponse
    {
        $update = $request->all();
        $updateId = $update['update_id'] ?? null;

        Log::info('Telegram webhook received', [
            'bot' => substr($botToken, 0, 10) . '...',
            'update_id' => $updateId,
        ]);

        // Handle callback_query (inline button presses)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return response()->json(['ok' => true]);
        }

        // Handle text messages (commands like /stop)
        if (isset($update['message']['text'])) {
            $this->handleTextMessage($update['message']);
        }

        return response()->json(['ok' => true]);
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['from']['id'] ?? null;

        if (!$chatId || !$data) {
            return;
        }

        // Format: "m:{short_id}:{action_code}" (max 64 bytes)
        $parts = explode(':', $data);
        if (count($parts) < 3 || $parts[0] !== 'm') {
            return;
        }

        $messageShortId = $parts[1];
        $actionCode = $parts[2];

        // Log the interaction
        $messageLog = MessageLog::where('external_msg_id', 'like', "%{$messageShortId}%")->first();
        if ($messageLog) {
            $messageLog->update([
                'interaction_type' => $actionCode,
                'replied_at' => now(),
            ]);
        }

        Log::info("Telegram callback: chat={$chatId}, action={$actionCode}");
    }

    private function handleTextMessage(array $message): void
    {
        $text = strtolower(trim($message['text'] ?? ''));
        $chatId = $message['from']['id'] ?? null;

        if (!$chatId) {
            return;
        }

        // Handle /stop opt-out
        if ($text === '/stop' || $text === 'stop') {
            $chatter = Chatter::where('telegram_id', (string) $chatId)->first();
            if ($chatter) {
                SuppressionList::firstOrCreate(
                    ['chatter_id' => $chatter->id, 'channel' => 'telegram'],
                    ['reason' => 'opt_out', 'source' => 'telegram_stop_command']
                );

                // Send confirmation
                try {
                    $botToken = config('telegram.bot_token');
                    $telegram = new \Telegram\Bot\Api($botToken);
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'You have been unsubscribed. You will no longer receive messages. Send /start to re-subscribe.',
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Failed to send stop confirmation: {$e->getMessage()}");
                }

                Log::info("Chatter {$chatter->id} opted out via Telegram /stop");
            }
            return;
        }

        // Handle /start re-subscribe
        if ($text === '/start') {
            $chatter = Chatter::where('telegram_id', (string) $chatId)->first();
            if ($chatter) {
                SuppressionList::where('chatter_id', $chatter->id)
                    ->where('channel', 'telegram')
                    ->where('reason', 'opt_out')
                    ->whereNull('lifted_at')
                    ->update(['lifted_at' => now()]);

                Log::info("Chatter {$chatter->id} re-subscribed via Telegram /start");
            }
        }
    }
}
