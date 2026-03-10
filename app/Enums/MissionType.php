<?php

declare(strict_types=1);

namespace App\Enums;

enum MissionType: string
{
    case OneTime = 'one_time';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Recurring = 'recurring';
    case StreakBased = 'streak_based';
    case EventTriggered = 'event_triggered';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One Time',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Recurring => 'Recurring',
            self::StreakBased => 'Streak Based',
            self::EventTriggered => 'Event Triggered',
        };
    }
}
