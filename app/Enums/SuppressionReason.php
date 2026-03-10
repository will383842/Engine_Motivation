<?php

declare(strict_types=1);

namespace App\Enums;

enum SuppressionReason: string
{
    case OptOut = 'opt_out';
    case Blocked = 'blocked';
    case Bounced = 'bounced';
    case SpamReported = 'spam_reported';
    case GdprErasure = 'gdpr_erasure';
    case AdminManual = 'admin_manual';
    case SunsetPolicy = 'sunset_policy';
    case InvalidNumber = 'invalid_number';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::OptOut => 'Opt Out',
            self::Blocked => 'Blocked',
            self::Bounced => 'Bounced',
            self::SpamReported => 'Spam Reported',
            self::GdprErasure => 'GDPR Erasure',
            self::AdminManual => 'Admin Manual',
            self::SunsetPolicy => 'Sunset Policy',
            self::InvalidNumber => 'Invalid Number',
            self::Duplicate => 'Duplicate',
        };
    }
}
