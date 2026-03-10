<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\FraudFlag;
use App\Models\FraudRule;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    public function __construct(
        private AdminNotifier $adminNotifier,
    ) {}

    public function check(Chatter $chatter, string $eventType, array $eventData = []): void
    {
        $rules = FraudRule::where('is_active', true)->get();

        foreach ($rules as $rule) {
            if ($this->matchesRule($chatter, $eventType, $eventData, $rule)) {
                $this->flagChatter($chatter, $rule, $eventData);
            }
        }
    }

    private function matchesRule(Chatter $chatter, string $eventType, array $eventData, FraudRule $rule): bool
    {
        $conditions = $rule->conditions;

        return match ($rule->rule_type) {
            'ip_threshold' => $this->checkIpThreshold($eventData, $conditions),
            'circular_referral' => $this->checkCircularReferral($chatter, $eventData),
            'velocity_check' => $this->checkVelocity($chatter, $conditions),
            default => false,
        };
    }

    private function checkIpThreshold(array $eventData, array $conditions): bool
    {
        $ip = $eventData['ip'] ?? null;
        if (!$ip) {
            return false;
        }
        $maxClicks = $conditions['max_clicks_per_ip'] ?? 5;
        $periodHours = $conditions['period_hours'] ?? 24;
        $count = FraudFlag::where('evidence->ip', $ip)
            ->where('created_at', '>=', now()->subHours($periodHours))
            ->count();
        return $count >= $maxClicks;
    }

    private function checkCircularReferral(Chatter $chatter, array $eventData): bool
    {
        $referralUid = $eventData['referralUid'] ?? null;
        if (!$referralUid) {
            return false;
        }
        $referral = Chatter::where('firebase_uid', $referralUid)->first();
        // Check if referral was referred by this chatter (circular)
        return $referral && $referral->extra['referrer_uid'] === $chatter->firebase_uid;
    }

    private function checkVelocity(Chatter $chatter, array $conditions): bool
    {
        $maxEvents = $conditions['max_events'] ?? 10;
        $periodMinutes = $conditions['period_minutes'] ?? 60;
        $count = $chatter->chatterEvents()
            ->where('occurred_at', '>=', now()->subMinutes($periodMinutes))
            ->count();
        return $count >= $maxEvents;
    }

    private function flagChatter(Chatter $chatter, FraudRule $rule, array $evidence): void
    {
        $severity = match ($rule->action) {
            'suspend', 'block_xp' => 'high',
            'flag' => 'medium',
            default => 'low',
        };

        FraudFlag::create([
            'chatter_id' => $chatter->id,
            'flag_type' => $rule->rule_type,
            'severity' => $severity,
            'evidence' => $evidence,
        ]);

        if ($severity === 'high') {
            $this->adminNotifier->alert('warning', 'fraud', "Fraud detected for chatter {$chatter->display_name}: {$rule->name}", ['dashboard', 'telegram']);
        }
    }
}
