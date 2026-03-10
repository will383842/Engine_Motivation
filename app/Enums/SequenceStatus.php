<?php

declare(strict_types=1);

namespace App\Enums;

enum SequenceStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Archived => 'Archived',
        };
    }
}
