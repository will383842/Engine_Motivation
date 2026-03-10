<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\SuppressionList;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppSender
{
    /** Error codes that indicate permanent failure — never retry */
    private const PERMANENT_FAILURES = [
        21211, // Invalid 'To' phone number
        21214, // 'To' phone number cannot be reached
        21217, // Phone number does not appear to be valid
        21610, // Message violates a blocklist rule
        21614, // 'To' number is not a valid mobile number
    ];

    /** Error codes indicating account/number issues */
    private const ACCOUNT_FAILURES = [
        20003, // Authentication error (account suspended)
        20008, // Account not active
    ];

    /** Error codes indicating WhatsApp-specific issues */
    private const WHATSAPP_FAILURES = [
        63003, // Channel not installed (WhatsApp not set up)
        63015, // Message delivery — User's phone is off or unreachable
        63016, // Message delivery — User is rate limited
        63024, // Twilio unable to deliver — template not approved
        63032, // User has not opted in
    ];

    /** Rate limit error */
    private const RATE_LIMIT = 20429;

    /** 8-week warm-up schedule: week => max daily messages */
    private const WARMUP_SCHEDULE = [
        1 => 50,
        2 => 100,
        3 => 200,
        4 => 350,
        5 => 500,
        6 => 750,
        7 => 1000,
        8 => 1500,
        // After week 8: unlimited (use number's daily_limit)
    ];

    /** Monthly budget cap in cents ($300) */
    private const MONTHLY_BUDGET_CAP_CENTS = 30000;

    /** Content validation: max emojis allowed */
    private const MAX_EMOJIS = 3;

    /** Content validation: min personalized words in first line */
    private const MIN_PERSONALIZED_WORDS = 10;

    public function __construct(
        private WhatsAppNumberRouter $numberRouter,
        private TemplateRenderer $templateRenderer,
        private WhatsAppCircuitBreaker $circuitBreaker,
        private FeeCalculationService $feeService,
        private AdminNotifier $adminNotifier,
    ) {}

    public function send(Chatter $chatter, string $templateSlug, array $variables = []): ?array
    {
        if (!$chatter->whatsapp_phone) {
            return null;
        }

        // Never send WhatsApp if Telegram is linked (irréversible)
        if ($chatter->telegram_id) {
            return null;
        }

        $number = $this->numberRouter->resolve($chatter);
        if (!$number || !$this->circuitBreaker->canSend($number)) {
            Log::warning("No available WhatsApp number for chatter {$chatter->id}");
            return null;
        }

        $message = $this->templateRenderer->render($templateSlug, 'whatsapp', $chatter->language, $variables);
        if (!$message) {
            return null;
        }

        // Content validation
        if (!$this->validateContent($message['body'] ?? '')) {
            Log::warning("WhatsApp content validation failed for template {$templateSlug}");
            return null;
        }

        // Warm-up daily limit check
        if (!$this->checkWarmupLimit($number)) {
            Log::info("WhatsApp warm-up limit reached for number {$number->phone_number}");
            return null;
        }

        // Monthly budget cap check
        $costCents = $this->feeService->calculateCost($chatter->country ?? 'default');
        if (!$this->checkMonthlyBudget($number, $costCents)) {
            Log::warning("WhatsApp monthly budget cap reached for number {$number->phone_number}");
            return null;
        }

        try {
            $twilio = new TwilioClient(
                config('whatsapp.account_sid'),
                config('whatsapp.auth_token')
            );

            $twilioMessage = $twilio->messages->create(
                "whatsapp:{$chatter->whatsapp_phone}",
                [
                    'from' => "whatsapp:{$number->phone_number}",
                    'body' => $message['body'],
                    'statusCallback' => url('/api/webhooks/twilio/status'),
                ]
            );

            $this->circuitBreaker->recordSuccess($number);

            return [
                'message_id' => $twilioMessage->sid,
                'cost_cents' => $costCents,
                'sender_id' => $number->id,
            ];
        } catch (\Twilio\Exceptions\RestException $e) {
            $errorCode = $e->getCode();
            $this->circuitBreaker->recordFailure($number, $errorCode);

            // Permanent failures — add to suppression list
            if (in_array($errorCode, self::PERMANENT_FAILURES)) {
                SuppressionList::firstOrCreate(
                    ['chatter_id' => $chatter->id, 'channel' => 'whatsapp'],
                    ['reason' => 'invalid_number', 'source' => "twilio_error_{$errorCode}"]
                );
                Log::warning("WhatsApp permanent failure {$errorCode} for chatter {$chatter->id} — added to suppression list");
                return null;
            }

            // Account suspended — EMERGENCY alert
            if (in_array($errorCode, self::ACCOUNT_FAILURES)) {
                $this->adminNotifier->alert(
                    'emergency',
                    'whatsapp',
                    "Twilio account issue (error {$errorCode}): WhatsApp sending suspended. Use Telegram + Email only.",
                    ['telegram', 'email']
                );
                Log::critical("Twilio account failure: {$errorCode}");
                return null;
            }

            // WhatsApp-specific failures
            if (in_array($errorCode, self::WHATSAPP_FAILURES)) {
                if ($errorCode === 63032) {
                    // User not opted in — suppress
                    SuppressionList::firstOrCreate(
                        ['chatter_id' => $chatter->id, 'channel' => 'whatsapp'],
                        ['reason' => 'opt_out', 'source' => 'twilio_63032']
                    );
                }
                Log::warning("WhatsApp failure {$errorCode} for chatter {$chatter->id}");
                return null;
            }

            // Rate limited — don't throw, just return null for retry later
            if ($errorCode === self::RATE_LIMIT) {
                Log::info("WhatsApp rate limited for number {$number->phone_number}");
                return null;
            }

            throw $e;
        }
    }

    /**
     * 8-week warm-up: enforce progressive daily limits per number.
     */
    private function checkWarmupLimit(\App\Models\WhatsAppNumber $number): bool
    {
        $warmupWeek = $number->warmup_week ?? 0;
        if ($warmupWeek <= 0 || $warmupWeek > 8) {
            return true; // Fully warmed up
        }

        $dailyLimit = self::WARMUP_SCHEDULE[$warmupWeek] ?? 1500;
        $sentToday = (int) \Illuminate\Support\Facades\Redis::get("wa_num:{$number->id}:sent_today") ?: 0;

        return $sentToday < $dailyLimit;
    }

    /**
     * Monthly budget cap: $300 per number, alert at 80%.
     */
    private function checkMonthlyBudget(\App\Models\WhatsAppNumber $number, int $costCents): bool
    {
        $monthKey = "wa_num:{$number->id}:cost_month:" . now()->format('Y-m');
        $currentCost = (int) \Illuminate\Support\Facades\Redis::get($monthKey) ?: 0;

        // Alert at 80%
        $cap = self::MONTHLY_BUDGET_CAP_CENTS;
        if ($currentCost >= (int) ($cap * 0.8) && $currentCost < $cap) {
            $this->adminNotifier->alert(
                'warning',
                'whatsapp_budget',
                "WhatsApp number {$number->phone_number}: monthly budget at " . round($currentCost / $cap * 100) . "% (\${$currentCost}/\${$cap})",
                ['telegram', 'dashboard']
            );
        }

        if (($currentCost + $costCents) > $cap) {
            $this->adminNotifier->alert(
                'critical',
                'whatsapp_budget',
                "WhatsApp number {$number->phone_number}: monthly budget CAP REACHED (\$" . ($currentCost / 100) . "/\$" . ($cap / 100) . ")",
                ['telegram', 'email', 'dashboard']
            );
            return false;
        }

        // Track cost
        \Illuminate\Support\Facades\Redis::incrby($monthKey, $costCents);
        // Expire at end of month + 1 day buffer
        $daysLeft = now()->daysInMonth - now()->day + 1;
        \Illuminate\Support\Facades\Redis::expire($monthKey, $daysLeft * 86400);

        return true;
    }

    /**
     * Content validation: first 10 words must be personalized, max 3 emojis.
     */
    private function validateContent(string $body): bool
    {
        if (empty($body)) {
            return false;
        }

        // Count emojis (simplified: Unicode emoji ranges)
        $emojiCount = preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $body);
        if ($emojiCount > self::MAX_EMOJIS) {
            Log::info("WhatsApp content rejected: {$emojiCount} emojis (max " . self::MAX_EMOJIS . ")");
            return false;
        }

        // Check first line has at least MIN_PERSONALIZED_WORDS words
        $firstLine = strtok($body, "\n");
        $wordCount = str_word_count($firstLine);
        if ($wordCount < self::MIN_PERSONALIZED_WORDS) {
            Log::info("WhatsApp content rejected: first line has {$wordCount} words (min " . self::MIN_PERSONALIZED_WORDS . ")");
            return false;
        }

        return true;
    }
}
