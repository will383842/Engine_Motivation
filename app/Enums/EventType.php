<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case ChatterRegistered = 'chatter_registered';
    case ChatterTelegramLinked = 'chatter_telegram_linked';
    case ChatterFirstSale = 'chatter_first_sale';
    case ChatterSaleCompleted = 'chatter_sale_completed';
    case ChatterWithdrawal = 'chatter_withdrawal';
    case ChatterLevelUp = 'chatter_level_up';
    case ChatterStatusChanged = 'chatter_status_changed';
    case ChatterProfileUpdated = 'chatter_profile_updated';
    case ChatterReferralSignup = 'chatter_referral_signup';
    case ChatterReferralActivated = 'chatter_referral_activated';
    case ChatterDeleted = 'chatter_deleted';
    case ChatterClickTracked = 'chatter_click_tracked';
    case ChatterTrainingCompleted = 'chatter_training_completed';
    case ChatterZoomAttended = 'chatter_zoom_attended';
    case ChatterWithdrawalStatusChanged = 'chatter_withdrawal_status_changed';
    case ChatterCaptainPromoted = 'chatter_captain_promoted';
    case ChatterStreakFreezePurchased = 'chatter_streak_freeze_purchased';

    public function label(): string
    {
        return match ($this) {
            self::ChatterRegistered => 'Chatter Registered',
            self::ChatterTelegramLinked => 'Telegram Linked',
            self::ChatterFirstSale => 'First Sale',
            self::ChatterSaleCompleted => 'Sale Completed',
            self::ChatterWithdrawal => 'Withdrawal',
            self::ChatterLevelUp => 'Level Up',
            self::ChatterStatusChanged => 'Status Changed',
            self::ChatterProfileUpdated => 'Profile Updated',
            self::ChatterReferralSignup => 'Referral Signup',
            self::ChatterReferralActivated => 'Referral Activated',
            self::ChatterDeleted => 'Chatter Deleted',
            self::ChatterClickTracked => 'Click Tracked',
            self::ChatterTrainingCompleted => 'Training Completed',
            self::ChatterZoomAttended => 'Zoom Attended',
            self::ChatterWithdrawalStatusChanged => 'Withdrawal Status Changed',
            self::ChatterCaptainPromoted => 'Captain Promoted',
            self::ChatterStreakFreezePurchased => 'Streak Freeze Purchased',
        };
    }
}
