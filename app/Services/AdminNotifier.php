<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminNotifier
{
    public function alert(string $severity, string $category, string $message, array $channels = ['dashboard']): AdminAlert
    {
        $alert = AdminAlert::create([
            'severity' => $severity,
            'category' => $category,
            'message' => $message,
            'channels_notified' => $channels,
        ]);

        if (in_array('email', $channels) && in_array($severity, ['critical', 'emergency'])) {
            $this->sendEmail($alert);
        }

        if (in_array('telegram', $channels)) {
            $this->sendTelegramAlert($alert);
        }

        if (in_array('sms', $channels) && in_array($severity, ['critical', 'emergency'])) {
            $this->sendSms($alert);
        }

        if (in_array('voice', $channels) && $severity === 'emergency') {
            $this->sendVoiceCall($alert);
        }

        Log::channel('admin')->{$severity === 'emergency' ? 'critical' : $severity}($message, [
            'category' => $category,
            'alert_id' => $alert->id,
        ]);

        return $alert;
    }

    private function sendEmail(AdminAlert $alert): void
    {
        try {
            Mail::raw($alert->message, function ($mail) use ($alert) {
                $mail->to(config('mail.admin_email', 'admin@motivation.life-expat.com'))
                    ->subject("[{$alert->severity}] {$alert->category}: Motivation Engine Alert");
            });
        } catch (\Throwable $e) {
            Log::error("Failed to send admin email alert: {$e->getMessage()}");
        }
    }

    private function sendTelegramAlert(AdminAlert $alert): void
    {
        try {
            $adminChatId = config('telegram.admin_chat_id');
            if (!$adminChatId) {
                return;
            }
            $telegram = new \Telegram\Bot\Api(config('telegram.bot_token'));
            $emoji = match ($alert->severity) {
                'emergency' => '🚨',
                'critical' => '❌',
                'warning' => '⚠️',
                default => 'ℹ️',
            };
            $telegram->sendMessage([
                'chat_id' => $adminChatId,
                'text' => "{$emoji} [{$alert->severity}] {$alert->category}\n{$alert->message}",
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send Telegram admin alert: {$e->getMessage()}");
        }
    }

    /**
     * Send SMS alert via Twilio for critical/emergency.
     */
    private function sendSms(AdminAlert $alert): void
    {
        try {
            $adminPhone = config('services.admin_sms_phone');
            if (!$adminPhone) {
                return;
            }

            $twilio = new \Twilio\Rest\Client(
                config('whatsapp.account_sid'),
                config('whatsapp.auth_token')
            );

            $twilio->messages->create(
                $adminPhone,
                [
                    'from' => config('twilio.from_number'),
                    'body' => "[{$alert->severity}] {$alert->category}: " . mb_substr($alert->message, 0, 140),
                ]
            );
        } catch (\Throwable $e) {
            Log::error("Failed to send SMS admin alert: {$e->getMessage()}");
        }
    }

    /**
     * Voice call alert via Twilio for emergency only.
     * Reads the alert message aloud using TwiML <Say>.
     */
    private function sendVoiceCall(AdminAlert $alert): void
    {
        try {
            $adminPhone = config('services.admin_sms_phone');
            if (!$adminPhone) {
                return;
            }

            $twilio = new \Twilio\Rest\Client(
                config('whatsapp.account_sid'),
                config('whatsapp.auth_token')
            );

            $twiml = '<Response><Say language="en-US">Motivation Engine emergency alert. '
                . htmlspecialchars(mb_substr($alert->message, 0, 200))
                . '</Say><Pause length="1"/><Say language="en-US">Please check the dashboard immediately.</Say></Response>';

            $twilio->calls->create(
                $adminPhone,
                config('twilio.from_number'),
                ['twiml' => $twiml]
            );
        } catch (\Throwable $e) {
            Log::error("Failed to send voice admin alert: {$e->getMessage()}");
        }
    }
}
