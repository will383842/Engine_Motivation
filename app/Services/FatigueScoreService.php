<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterFatigueScore;
use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;

class FatigueScoreService
{
    public function calculate(Chatter $chatter, string $channel): float
    {
        $stats = $this->getStats($chatter, $channel, 7);

        $factors = [
            'ignored_ratio' => $this->ignoredRatio($stats) * 40,
            'frequency_excess' => $this->frequencyExcess($stats, $channel) * 20,
            'recency_decay' => $this->recencyDecay($stats) * 15,
            'consecutive_miss' => $this->consecutiveMiss($stats) * 25,
        ];

        return min(100, array_sum($factors));
    }

    public function getMultiplier(Chatter $chatter, string $channel): float
    {
        $score = ChatterFatigueScore::where('chatter_id', $chatter->id)
            ->where('channel', $channel)
            ->value('frequency_multiplier') ?? 1.0;

        return (float) $score;
    }

    public function recalculateAll(): int
    {
        $count = 0;
        Chatter::where('is_active', true)->chunkById(100, function ($chatters) use (&$count) {
            foreach ($chatters as $chatter) {
                foreach (['telegram', 'whatsapp'] as $channel) {
                    $score = $this->calculate($chatter, $channel);
                    $multiplier = match (true) {
                        $score <= 20 => 1.0,
                        $score <= 40 => 0.75,
                        $score <= 60 => 0.50,
                        $score <= 80 => 0.25,
                        default => 0.0,
                    };

                    ChatterFatigueScore::updateOrCreate(
                        ['chatter_id' => $chatter->id, 'channel' => $channel],
                        [
                            'fatigue_score' => $score,
                            'frequency_multiplier' => $multiplier,
                            'updated_at' => now(),
                        ]
                    );
                    $count++;
                }
            }
        });
        return $count;
    }

    private function getStats(Chatter $chatter, string $channel, int $days): array
    {
        $since = now()->subDays($days);
        $logs = MessageLog::where('chatter_id', $chatter->id)
            ->where('channel', $channel)
            ->where('created_at', '>=', $since)
            ->get();

        return [
            'sent' => $logs->count(),
            'interacted' => $logs->whereNotNull('read_at')->count() + $logs->where('interaction_type', '!=', null)->count(),
            'last_interaction' => $logs->whereNotNull('read_at')->max('read_at'),
            'consecutive_ignored' => ChatterFatigueScore::where('chatter_id', $chatter->id)
                ->where('channel', $channel)->value('consecutive_ignored') ?? 0,
            'channel' => $channel,
        ];
    }

    private function ignoredRatio(array $stats): float
    {
        if ($stats['sent'] === 0) return 0;
        return ($stats['sent'] - $stats['interacted']) / $stats['sent'];
    }

    private function frequencyExcess(array $stats, string $channel): float
    {
        $optimal = $channel === 'whatsapp' ? 4 : 7;
        if ($stats['sent'] <= $optimal) return 0;
        return min(1.0, ($stats['sent'] - $optimal) / $optimal);
    }

    private function recencyDecay(array $stats): float
    {
        if (!$stats['last_interaction']) return 1.0;
        $days = now()->diffInDays($stats['last_interaction']);
        return min(1.0, $days / 14);
    }

    private function consecutiveMiss(array $stats): float
    {
        return min(1.0, $stats['consecutive_ignored'] / 5);
    }
}
