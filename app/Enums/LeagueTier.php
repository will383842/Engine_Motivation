<?php

declare(strict_types=1);

namespace App\Enums;

enum LeagueTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Diamond = 'diamond';
    case Master = 'master';
    case Legend = 'legend';

    public function label(): string
    {
        return match ($this) {
            self::Bronze => 'Bronze',
            self::Silver => 'Silver',
            self::Gold => 'Gold',
            self::Platinum => 'Platinum',
            self::Diamond => 'Diamond',
            self::Master => 'Master',
            self::Legend => 'Legend',
        };
    }
}
