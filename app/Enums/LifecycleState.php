<?php

declare(strict_types=1);

namespace App\Enums;

enum LifecycleState: string
{
    case Registered = 'registered';
    case Onboarding = 'onboarding';
    case Active = 'active';
    case Declining = 'declining';
    case Dormant = 'dormant';
    case Churned = 'churned';
    case Sunset = 'sunset';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::Onboarding => 'Onboarding',
            self::Active => 'Active',
            self::Declining => 'Declining',
            self::Dormant => 'Dormant',
            self::Churned => 'Churned',
            self::Sunset => 'Sunset',
        };
    }
}
