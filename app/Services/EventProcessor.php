<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterBadge;
use App\Models\ChatterEvent;
use App\Models\ChatterSequence;
use App\Models\ConsentRecord;
use App\Models\NotificationPreference;
use App\Models\RevenueAttribution;
use App\Models\Sequence;
use App\Models\WebhookEvent;
use App\Events\ChatterRegistered;
use App\Events\SaleCompleted;
use App\Events\ChatterInteracted;
use App\Events\LevelUp;
use App\Events\WebhookReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventProcessor
{
    public function __construct(
        private StreakService $streakService,
        private MissionService $missionService,
        private GdprService $gdprService,
        private LevelService $levelService,
        private LeaderboardService $leaderboardService,
        private SocialProofService $socialProofService,
        private PsychologicalTriggersService $triggersService,
    ) {}

    public function process(WebhookEvent $event): void
    {
        $event->update(['status' => 'processing', 'attempts' => $event->attempts + 1]);

        try {
            $payload = $event->payload;
            $chatter = $this->resolveChatter($payload);

            if (!$chatter) {
                if ($event->event_type === 'chatter.registered') {
                    $chatter = $this->createChatter($payload);
                } else {
                    // Retry later — webhook arrived before chatter creation
                    if ($event->attempts < 5) {
                        $event->update(['status' => 'pending']);
                        return;
                    }
                    $event->update(['status' => 'skipped']);
                    Log::warning("Orphan webhook after 5 attempts: {$event->event_type}", ['event_id' => $event->id]);
                    return;
                }
            }

            ChatterEvent::create([
                'chatter_id' => $chatter->id,
                'event_type' => $event->event_type,
                'event_data' => $payload,
                'firebase_event_id' => $event->external_event_id,
                'occurred_at' => now(),
            ]);

            match ($event->event_type) {
                'chatter.registered' => $this->handleRegistered($chatter, $payload),
                'chatter.sale_completed' => $this->handleSaleCompleted($chatter, $payload),
                'chatter.first_sale' => $this->handleFirstSale($chatter, $payload),
                'chatter.telegram_linked' => $this->handleTelegramLinked($chatter, $payload),
                'chatter.withdrawal' => $this->handleWithdrawal($chatter, $payload),
                'chatter.level_up' => $this->handleLevelUp($chatter, $payload),
                'chatter.referral_signup' => $this->handleReferralSignup($chatter, $payload),
                'chatter.referral_activated' => $this->handleReferralActivated($chatter, $payload),
                'chatter.click_tracked' => $this->handleClickTracked($chatter, $payload),
                'chatter.training_completed' => $this->handleTrainingCompleted($chatter, $payload),
                'chatter.status_changed' => $this->handleStatusChanged($chatter, $payload),
                'chatter.profile_updated' => $this->handleProfileUpdated($chatter, $payload),
                'chatter.withdrawal_status_changed' => $this->handleWithdrawalStatusChanged($chatter, $payload),
                'chatter.zoom_attended' => $this->handleZoomAttended($chatter, $payload),
                'chatter.captain_promoted' => $this->handleCaptainPromoted($chatter, $payload),
                'chatter.streak_freeze_purchased' => $this->handleStreakFreezePurchased($chatter, $payload),
                'chatter.deleted' => $this->handleDeleted($chatter, $payload),
                default => Log::info("Unhandled event type: {$event->event_type}"),
            };

            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error("Event processing failed: {$e->getMessage()}", ['event_id' => $event->id]);
            $event->update(['status' => $event->attempts >= 5 ? 'failed' : 'pending']);
            throw $e;
        }
    }

    private function handleRegistered(Chatter $chatter, array $payload): void
    {
        // 1. Enroll in onboarding sequence
        $onboarding = Sequence::where('trigger_event', 'chatter.registered')
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->first();

        if ($onboarding) {
            $firstStep = $onboarding->steps()->orderBy('step_order')->first();
            ChatterSequence::create([
                'chatter_id' => $chatter->id,
                'sequence_id' => $onboarding->id,
                'current_step_id' => $firstStep?->id,
                'current_step_order' => 1,
                'status' => 'active',
                'enrolled_at' => now(),
                'next_step_at' => now(),
            ]);
        }

        // 2. Assign onboarding missions
        $this->missionService->assignOnboardingMissions($chatter);

        // 3. Award "Bienvenue" badge
        $this->awardBadgeBySlug($chatter, 'welcome');

        // 4. Create default notification preferences
        foreach (['telegram', 'whatsapp', 'dashboard'] as $channel) {
            NotificationPreference::firstOrCreate(
                ['chatter_id' => $chatter->id, 'channel' => $channel],
                ['is_opted_in' => true],
            );
        }

        // 5. Create consent record
        ConsentRecord::create([
            'chatter_id' => $chatter->id,
            'consent_type' => 'messaging',
            'granted' => true,
            'version' => '1.0',
            'granted_at' => now(),
        ]);

        // 6. Social proof + endowed progress
        $this->socialProofService->pushNewChatter($chatter);
        $this->triggersService->applyEndowedProgress($chatter);

        // 7. Calculate initial engagement score
        app(EngagementScoreService::class)->calculate($chatter);

        event(new ChatterRegistered($chatter));
    }

    private function handleSaleCompleted(Chatter $chatter, array $payload): void
    {
        $commissionCents = $payload['commissionCents'] ?? 0;

        $this->streakService->recordActivity($chatter);
        $this->missionService->incrementProgress($chatter, 'sale_completed');
        $this->levelService->awardXp($chatter, 'sale_completed');
        $this->leaderboardService->recordSale($chatter, $commissionCents);

        DB::transaction(function () use ($chatter, $commissionCents) {
            $chatter->lockForUpdate()->refresh();
            $chatter->increment('total_sales');
            $chatter->increment('balance_cents', $commissionCents);
            $chatter->increment('lifetime_earnings_cents', $commissionCents);
            $chatter->update(['last_active_at' => now()]);
        });

        // Reverse lifecycle if dormant/churned
        if (in_array($chatter->lifecycle_state, ['declining', 'dormant', 'churned'])) {
            $chatter->update(['lifecycle_state' => 'active', 'lifecycle_changed_at' => now()]);
        }

        // Revenue attribution (last-touch, 7-day window)
        $lastMessage = DB::table('message_logs')
            ->where('chatter_id', $chatter->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->first();

        RevenueAttribution::create([
            'chatter_id' => $chatter->id,
            'commission_cents' => $commissionCents,
            'attributed_to' => $lastMessage ? 'message' : 'organic',
            'attributed_message_id' => $lastMessage?->id,
            'attribution_window_hours' => 168,
            'firebase_event_id' => $payload['callId'] ?? null,
        ]);

        // Social proof + lucky commission
        $this->socialProofService->pushCommission($chatter, $commissionCents);
        $this->triggersService->rollLuckyCommission($chatter);

        // Check sale-count badges
        $this->checkSaleBadges($chatter);

        // Check secret missions
        $this->checkSecretMissions($chatter);

        event(new SaleCompleted(
            $chatter,
            $commissionCents,
            $payload['callId'] ?? '',
            $payload['isFirstSale'] ?? false,
        ));
    }

    private function handleFirstSale(Chatter $chatter, array $payload): void
    {
        $this->levelService->awardXp($chatter, 'first_sale');
        $this->socialProofService->pushFirstSale($chatter);
        $this->awardBadgeBySlug($chatter, 'first_client');
        $this->handleSaleCompleted($chatter, array_merge($payload, ['isFirstSale' => true]));
    }

    private function handleTelegramLinked(Chatter $chatter, array $payload): void
    {
        $chatter->update([
            'telegram_id' => $payload['telegramId'] ?? null,
            'preferred_channel' => 'telegram',
            'whatsapp_opted_in' => false,
        ]);

        $this->levelService->awardXp($chatter, 'telegram_linked');

        // Award telegram_linked badge
        $this->awardBadgeBySlug($chatter, 'telegram_linked');

        // Update notification preferences
        NotificationPreference::updateOrCreate(
            ['chatter_id' => $chatter->id, 'channel' => 'telegram'],
            ['is_opted_in' => true],
        );
        NotificationPreference::updateOrCreate(
            ['chatter_id' => $chatter->id, 'channel' => 'whatsapp'],
            ['is_opted_in' => false, 'opted_out_at' => now()],
        );

        // Cancel any WhatsApp messages in queue for this chatter
        DB::table('jobs')
            ->where('payload', 'like', "%{$chatter->id}%")
            ->where('queue', 'whatsapp')
            ->delete();

        // Switch active sequences to Telegram channel
        ChatterSequence::where('chatter_id', $chatter->id)
            ->where('status', 'active')
            ->update(['metadata->channel_override' => 'telegram']);

        event(new ChatterInteracted($chatter, 'telegram_linked'));
    }

    private function handleWithdrawal(Chatter $chatter, array $payload): void
    {
        event(new ChatterInteracted($chatter, 'withdrawal'));
    }

    private function handleLevelUp(Chatter $chatter, array $payload): void
    {
        $oldLevel = $payload['oldLevel'] ?? $chatter->level;
        $newLevel = $payload['newLevel'] ?? $chatter->level + 1;
        $chatter->update(['level' => $newLevel]);
        $this->socialProofService->pushLevelUp($chatter, $newLevel);
        event(new LevelUp($chatter, $oldLevel, $newLevel));
    }

    private function handleReferralSignup(Chatter $chatter, array $payload): void
    {
        $this->streakService->recordActivity($chatter);
        $this->missionService->incrementProgress($chatter, 'referral_signup');
        $this->levelService->awardXp($chatter, 'referral_signup');
        $this->leaderboardService->recordRecruitment($chatter);
        $this->socialProofService->pushRecruitment($chatter);
        $chatter->update(['last_active_at' => now()]);
        event(new ChatterInteracted($chatter, 'referral_signup', $payload));
    }

    private function handleReferralActivated(Chatter $chatter, array $payload): void
    {
        $bonusCents = $payload['activationBonusCents'] ?? 0;
        if ($bonusCents > 0) {
            $chatter->increment('balance_cents', $bonusCents);
        }
        $this->streakService->recordActivity($chatter);
        $this->missionService->incrementProgress($chatter, 'referral_activated');
        $this->levelService->awardXp($chatter, 'referral_activated');
        $chatter->update(['last_active_at' => now()]);
        event(new ChatterInteracted($chatter, 'referral_activated', $payload));
    }

    private function handleClickTracked(Chatter $chatter, array $payload): void
    {
        $this->streakService->recordActivity($chatter);
        $this->missionService->incrementProgress($chatter, 'click_tracked');
        $this->levelService->awardXp($chatter, 'click_tracked');
        $chatter->update(['last_active_at' => now()]);
        event(new ChatterInteracted($chatter, 'click_tracked'));
    }

    private function handleTrainingCompleted(Chatter $chatter, array $payload): void
    {
        $this->streakService->recordActivity($chatter);
        $this->missionService->incrementProgress($chatter, 'training_completed');
        $this->levelService->awardXp($chatter, 'training_completed');
        $chatter->update(['last_active_at' => now()]);
        event(new ChatterInteracted($chatter, 'training_completed'));
    }

    private function handleStatusChanged(Chatter $chatter, array $payload): void
    {
        $newStatus = $payload['status'] ?? $payload['newStatus'] ?? null;
        if ($newStatus) {
            $chatter->update(['is_active' => $newStatus === 'active']);
        }
        event(new ChatterInteracted($chatter, 'status_changed', $payload));
    }

    private function handleProfileUpdated(Chatter $chatter, array $payload): void
    {
        $updatable = ['display_name', 'email', 'phone', 'language', 'country', 'timezone', 'whatsapp_phone'];
        $updates = array_intersect_key($payload, array_flip($updatable));
        if (!empty($updates)) {
            $chatter->update($updates);
        }
        $this->levelService->awardXp($chatter, 'profile_updated');
        event(new ChatterInteracted($chatter, 'profile_updated'));
    }

    private function handleWithdrawalStatusChanged(Chatter $chatter, array $payload): void
    {
        $status = $payload['status'] ?? $payload['newStatus'] ?? null;
        if ($status === 'failed' || $status === 'rejected') {
            $refundCents = $payload['totalDebited'] ?? $payload['amountCents'] ?? 0;
            if ($refundCents > 0) {
                $chatter->increment('balance_cents', $refundCents);
            }
        }
        event(new ChatterInteracted($chatter, 'withdrawal_status_changed', $payload));
    }

    private function handleZoomAttended(Chatter $chatter, array $payload): void
    {
        $this->streakService->recordActivity($chatter);
        $this->missionService->incrementProgress($chatter, 'zoom_attended');
        $this->levelService->awardXp($chatter, 'zoom_attended');
        $chatter->update(['last_active_at' => now()]);
        event(new ChatterInteracted($chatter, 'zoom_attended'));
    }

    private function handleCaptainPromoted(Chatter $chatter, array $payload): void
    {
        $chatter->update(['extra' => array_merge($chatter->extra ?? [], ['is_captain' => true])]);
        $this->missionService->incrementProgress($chatter, 'captain_promoted');
        $this->levelService->awardXp($chatter, 'captain_promoted');
        event(new ChatterInteracted($chatter, 'captain_promoted'));
    }

    private function handleStreakFreezePurchased(Chatter $chatter, array $payload): void
    {
        $cost = $payload['costCents'] ?? 200; // $2 default
        $chatter->decrement('balance_cents', $cost);
        $this->streakService->freezeStreak($chatter);
        event(new ChatterInteracted($chatter, 'streak_freeze_purchased'));
    }

    private function handleDeleted(Chatter $chatter, array $payload): void
    {
        $this->gdprService->anonymize($chatter);
    }

    private function resolveChatter(array $payload): ?Chatter
    {
        $uid = $payload['uid'] ?? $payload['data']['uid'] ?? null;
        return $uid ? Chatter::where('firebase_uid', $uid)->first() : null;
    }

    private function createChatter(array $payload): Chatter
    {
        return Chatter::create([
            'firebase_uid' => $payload['uid'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'display_name' => $payload['displayName'] ?? 'Chatter',
            'language' => $payload['language'] ?? 'en',
            'country' => $payload['country'] ?? null,
            'timezone' => $payload['timezone'] ?? 'UTC',
            'affiliate_code_client' => $payload['affiliateCodeClient'] ?? null,
            'affiliate_code_recruitment' => $payload['affiliateCodeRecruitment'] ?? null,
            'whatsapp_phone' => $payload['phone'] ?? null,
            'whatsapp_opted_in' => true,
            'preferred_channel' => 'whatsapp',
            'lifecycle_state' => 'onboarding',
            'last_active_at' => now(),
        ]);
    }

    private function awardBadgeBySlug(Chatter $chatter, string $slug): void
    {
        $badge = \App\Models\Badge::where('slug', $slug)->first();
        if (!$badge) {
            return;
        }

        ChatterBadge::firstOrCreate(
            ['chatter_id' => $chatter->id, 'badge_id' => $badge->id],
            ['awarded_at' => now()],
        );

        $chatter->increment('badges_count');

        if ($badge->xp_reward > 0) {
            $this->levelService->awardXp($chatter, 'badge_earned');
        }
    }

    private function checkSaleBadges(Chatter $chatter): void
    {
        $sales = $chatter->total_sales;
        $badgeSlugs = match (true) {
            $sales >= 100 => ['clients_100', 'clients_50', 'clients_10'],
            $sales >= 50 => ['clients_50', 'clients_10'],
            $sales >= 10 => ['clients_10'],
            default => [],
        };

        foreach ($badgeSlugs as $slug) {
            $this->awardBadgeBySlug($chatter, $slug);
        }
    }

    /**
     * Check secret missions that are event-triggered and never displayed in advance.
     * - Lightning Start: first sale within 1h of registration
     * - Pentakill: 5 sales within 24h
     * - Midnight Hustler: sale between 00:00-05:00 local time
     * - Phoenix (Comeback King): sale after 30+ days dormant
     */
    private function checkSecretMissions(Chatter $chatter): void
    {
        // Lightning Start: conversion < 1h after registration
        if ($chatter->created_at && $chatter->created_at->diffInMinutes(now()) <= 60) {
            if ($chatter->total_sales === 1) {
                $this->missionService->incrementProgress($chatter, 'lightning_start');
                $this->awardBadgeBySlug($chatter, 'lightning_start');
            }
        }

        // Pentakill: 5 sales in 24h
        $salesLast24h = ChatterEvent::where('chatter_id', $chatter->id)
            ->where('event_type', 'chatter.sale_completed')
            ->where('occurred_at', '>=', now()->subHours(24))
            ->count();

        if ($salesLast24h >= 5) {
            $this->awardBadgeBySlug($chatter, 'pentakill');
        }

        // Night Owl: sale between 00:00-05:00 local time
        $tz = $chatter->timezone ?? 'UTC';
        $localHour = now()->timezone($tz)->hour;
        if ($localHour >= 0 && $localHour < 5) {
            $this->awardBadgeBySlug($chatter, 'night_owl');
        }

        // Comeback King: sale after 30+ days dormant/churned
        if (in_array($chatter->getOriginal('lifecycle_state'), ['dormant', 'churned'])) {
            $chatter->update(['extra' => array_merge($chatter->extra ?? [], ['comeback' => true])]);
            $this->awardBadgeBySlug($chatter, 'comeback_king');
        }
    }
}
