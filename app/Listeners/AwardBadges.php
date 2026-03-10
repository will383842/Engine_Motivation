<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BadgeEarned;
use App\Events\ChatterInteracted;
use App\Events\ChatterRegistered;
use App\Events\MissionCompleted;
use App\Events\SaleCompleted;
use App\Models\Badge;
use App\Models\Chatter;
use App\Models\ChatterBadge;
use App\Models\ChatterEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Attribute\AsEventHandler;

class AwardBadges implements ShouldQueue
{
    public string $queue = 'high';

    public function subscribe($events): array
    {
        return [
            SaleCompleted::class => 'handleSale',
            MissionCompleted::class => 'handleMission',
            ChatterRegistered::class => 'handleRegistered',
            ChatterInteracted::class => 'handleInteracted',
        ];
    }

    public function handleSale(SaleCompleted $event): void
    {
        $this->checkBadges($event->chatter);
    }

    public function handleMission(MissionCompleted $event): void
    {
        $this->checkBadges($event->chatter);
    }

    public function handleRegistered(ChatterRegistered $event): void
    {
        $this->checkBadges($event->chatter);
    }

    public function handleInteracted(ChatterInteracted $event): void
    {
        $this->checkBadges($event->chatter);
    }

    private function checkBadges(Chatter $chatter): void
    {
        $chatter->refresh();
        $badges = Badge::all();
        $existingBadgeIds = ChatterBadge::where('chatter_id', $chatter->id)
            ->pluck('badge_id')
            ->toArray();

        foreach ($badges as $badge) {
            if (in_array($badge->id, $existingBadgeIds)) {
                continue;
            }

            $criteria = $badge->criteria ?? [];
            $earned = $this->evaluateCriteria($chatter, $criteria);

            if ($earned) {
                ChatterBadge::create([
                    'chatter_id' => $chatter->id,
                    'badge_id' => $badge->id,
                    'awarded_at' => now(),
                ]);
                $chatter->increment('badges_count');
                event(new BadgeEarned($chatter, $badge));
            }
        }
    }

    private function evaluateCriteria(Chatter $chatter, array $criteria): bool
    {
        $type = $criteria['type'] ?? '';
        $count = $criteria['count'] ?? 999999;
        $field = $criteria['field'] ?? null;

        return match ($type) {
            // Sales count badges (first_client, clients_10, clients_50, clients_100)
            'sales_count' => ($chatter->total_sales ?? 0) >= $count,

            // Streak badges (streak_7, streak_14, streak_30, streak_100, streak_365)
            'streak' => ($chatter->current_streak ?? 0) >= $count,
            'longest_streak' => ($chatter->longest_streak ?? 0) >= $count,

            // Earnings badges (earned_100, earned_500, earned_1000)
            'earnings' => ($chatter->lifetime_earnings_cents ?? 0) >= $count,

            // Level badges (level_2, level_3, level_4, level_5)
            'level' => ($chatter->level ?? 1) >= $count,

            // Recruitment badges (first_recruitment, recruits_3, recruits_5, recruits_10, recruits_25, recruits_50)
            'recruits' => ChatterEvent::where('chatter_id', $chatter->id)
                ->where('event_type', 'chatter.referral_signup')
                ->count() >= $count,

            // Team badges (team_10, team_25, team_50, team_100)
            'team_size' => ChatterEvent::where('chatter_id', $chatter->id)
                ->where('event_type', 'chatter.referral_activated')
                ->count() >= $count,

            // Team earnings badges (team_earned_1000, team_earned_5000)
            'team_earnings' => ($chatter->extra['team_earnings_cents'] ?? 0) >= $count,

            // Profile badge
            'profile_complete' => $this->isProfileComplete($chatter),

            // Training badges (training_first, training_complete)
            'training_count' => ChatterEvent::where('chatter_id', $chatter->id)
                ->where('event_type', 'chatter.training_completed')
                ->count() >= $count,

            // Telegram badge
            'telegram_linked' => !empty($chatter->telegram_id),

            // Zoom badge
            'zoom_count' => ChatterEvent::where('chatter_id', $chatter->id)
                ->where('event_type', 'chatter.zoom_attended')
                ->count() >= $count,

            // Share badge (first_share)
            'clicks' => ChatterEvent::where('chatter_id', $chatter->id)
                ->where('event_type', 'chatter.click_tracked')
                ->count() >= $count,

            // Multi-platform badge
            'multi_platform' => !empty($chatter->telegram_id)
                && !empty($chatter->whatsapp_phone)
                && !empty($chatter->email),

            // Speed runner (sold within 24h of registration)
            'speed_runner' => $this->checkSpeedRunner($chatter),

            // Night owl (sale between 00:00-05:00 local)
            'night_owl' => $this->checkNightOwl($chatter),

            // Comeback king (sale after 30+ days dormant)
            'comeback' => ($chatter->extra['comeback'] ?? false) === true,

            // Top monthly leaderboard
            'top_monthly' => ($criteria['rank'] ?? 999) >= 1
                && ($chatter->extra['best_monthly_rank'] ?? 999) <= ($criteria['rank'] ?? 1),

            // Perfectionist (all daily missions completed 7 days straight)
            'perfect_week' => ($chatter->extra['perfect_weeks'] ?? 0) >= $count,

            // Generic field check on chatter model
            'field' => $field && ($chatter->{$field} ?? 0) >= $count,

            default => false,
        };
    }

    private function isProfileComplete(Chatter $chatter): bool
    {
        return !empty($chatter->display_name)
            && !empty($chatter->email)
            && !empty($chatter->phone)
            && !empty($chatter->country)
            && !empty($chatter->language)
            && !empty($chatter->timezone);
    }

    private function checkSpeedRunner(Chatter $chatter): bool
    {
        if (!$chatter->created_at) {
            return false;
        }

        return ChatterEvent::where('chatter_id', $chatter->id)
            ->where('event_type', 'chatter.sale_completed')
            ->where('occurred_at', '>=', $chatter->created_at)
            ->where('occurred_at', '<=', $chatter->created_at->addHours(24))
            ->exists();
    }

    private function checkNightOwl(Chatter $chatter): bool
    {
        return ChatterEvent::where('chatter_id', $chatter->id)
            ->where('event_type', 'chatter.sale_completed')
            ->whereRaw("EXTRACT(HOUR FROM occurred_at) BETWEEN 0 AND 5")
            ->exists();
    }
}
