<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\SuppressionList;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TelegramSender
{
    public function __construct(
        private TelegramBotRouter $botRouter,
        private TemplateRenderer $templateRenderer,
        private TelegramCircuitBreaker $circuitBreaker,
        private AdminNotifier $adminNotifier,
    ) {}

    public function send(Chatter $chatter, string $templateSlug, array $variables = []): ?array
    {
        if (!$chatter->telegram_id) {
            return null;
        }

        $bot = $this->botRouter->resolve($chatter);
        if (!$bot || !$this->circuitBreaker->canSend($bot)) {
            Log::warning("No available Telegram bot for chatter {$chatter->id}");
            return null;
        }

        $message = $this->templateRenderer->render($templateSlug, 'telegram', $chatter->language, $variables);
        if (!$message) {
            return null;
        }

        try {
            $telegram = new \Telegram\Bot\Api($bot->bot_token_encrypted);
            $response = $telegram->sendMessage([
                'chat_id' => $chatter->telegram_id,
                'text' => $message['body'],
                'parse_mode' => 'HTML',
                'reply_markup' => $message['buttons'] ?? null,
            ]);

            $this->circuitBreaker->recordSuccess($bot);

            return [
                'message_id' => (string) $response->getMessageId(),
                'cost_cents' => 0,
                'sender_id' => $bot->id,
            ];
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            $errorCode = $e->getCode();
            $this->circuitBreaker->recordFailure($bot, $errorCode);

            // 403 Forbidden = bot blocked by user — add to suppression, NO WhatsApp fallback
            if ($errorCode === 403) {
                SuppressionList::firstOrCreate(
                    ['chatter_id' => $chatter->id, 'channel' => 'telegram'],
                    ['reason' => 'blocked', 'source' => 'telegram_403']
                );
                Log::info("Telegram bot blocked by chatter {$chatter->id} — added to suppression list");

                // Alert if too many blocks
                $blocksToday = (int) Redis::incr("tg_blocks_today:{$bot->id}");
                Redis::expire("tg_blocks_today:{$bot->id}", 86400);
                if ($blocksToday > 5) {
                    $this->adminNotifier->alert(
                        'warning',
                        'telegram',
                        "Bot {$bot->bot_username}: {$blocksToday} blocks in 24h",
                        ['dashboard', 'telegram']
                    );
                }

                return null;
            }

            throw $e;
        }
    }

    /**
     * Send a raw message directly to a chat ID (for admin alerts, etc.)
     */
    public function sendRaw(string $chatId, string $text): void
    {
        $botToken = config('telegram.bot_token');
        $telegram = new \Telegram\Bot\Api($botToken);
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }
}
