<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterEngagementScore;
use Illuminate\Support\Facades\DB;

class EngagementScoreService
{
    public function calculate(Chatter $chatter): float
    {
        $activity = $this->activityScore($chatter);
        $revenue = $this->revenueScore($chatter);
        $responsiveness = $this->responsivenessScore($chatter);
        $gamification = $this->gamificationScore($chatter);
        $growth = $this->growthScore($chatter);

        return round(
            $activity * 0.25 +
            $revenue * 0.30 +
            $responsiveness * 0.20 +
            $gamification * 0.15 +
            $growth * 0.10,
            2
        );
    }

    public function recalculateAll(): int
    {
        $count = 0;
        Chatter::where('is_active', true)->chunkById(100, function ($chatters) use (&$count) {
            foreach ($chatters as $chatter) {
                $score = $this->calculate($chatter);
                $existing = ChatterEngagementScore::where('chatter_id', $chatter->id)->first();
                $trend = 'stable';
                if ($existing) {
                    $trend = $score > $existing->engagement_score + 2 ? 'rising'
                        : ($score < $existing->engagement_score - 2 ? 'declining' : 'stable');
                }

                ChatterEngagementScore::updateOrCreate(
                    ['chatter_id' => $chatter->id],
                    [
                        'engagement_score' => $score,
                        'activity_score' => $this->activityScore($chatter),
                        'revenue_score' => $this->revenueScore($chatter),
                        'responsiveness_score' => $this->responsivenessScore($chatter),
                        'gamification_score' => $this->gamificationScore($chatter),
                        'growth_score' => $this->growthScore($chatter),
                        'trend' => $trend,
                    ]
                );
                $count++;
            }
        });
        return $count;
    }

    private function activityScore(Chatter $chatter): float
    {
        $events = $chatter->chatterEvents()->where('occurred_at', '>=', now()->subDays(7))->count();
        return min(100, $events * 10);
    }

    private function revenueScore(Chatter $chatter): float
    {
        $earnings30d = $chatter->revenueAttributions()
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('commission_cents');
        return min(100, ($earnings30d / 10000) * 100);
    }

    private function responsivenessScore(Chatter $chatter): float
    {
        $total = $chatter->messageLogs()->where('created_at', '>=', now()->subDays(7))->count();
        $read = $chatter->messageLogs()->where('created_at', '>=', now()->subDays(7))->whereNotNull('read_at')->count();
        return $total > 0 ? round(($read / $total) * 100, 2) : 50;
    }

    private function gamificationScore(Chatter $chatter): float
    {
        $badges = $chatter->badges_count;
        $streak = min($chatter->current_streak, 30);
        $missions = $chatter->missions()->where('chatter_missions.status', 'completed')
            ->where('chatter_missions.completed_at', '>=', now()->subDays(7))->count();
        return min(100, ($badges * 3) + ($streak * 2) + ($missions * 10));
    }

    private function growthScore(Chatter $chatter): float
    {
        return 50; // Placeholder: compare this week vs last week
    }
}
