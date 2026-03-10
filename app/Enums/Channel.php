<?php

declare(strict_types=1);

namespace App\Enums;

enum Channel: string
{
    case Telegram = 'telegram';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Push = 'push';
    case Sms = 'sms';
    case Dashboard = 'dashboard';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::Push => 'Push Notification',
            self::Sms => 'SMS',
            self::Dashboard => 'Dashboard',
        };
    }
}
