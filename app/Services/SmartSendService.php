<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ChatterSendTimeProfile;
use Carbon\Carbon;

class SmartSendService
{
    public function getOptimalSendTime(Chatter $chatter): ?Carbon
    {
        $profile = ChatterSendTimeProfile::where('chatter_id', $chatter->id)->first();
        if (!$profile || $profile->confidence < 0.3 || $profile->sample_size < 5) {
            // Default: 10h local time if not enough data
            $tz = $chatter->timezone ?: 'UTC';
            $default = Carbon::now($tz)->setHour(10)->setMinute(rand(0, 30))->setSecond(0);
            if ($default->isPast()) {
                $default->addDay();
            }
            return $default;
        }

        $tz = $chatter->timezone ?: 'UTC';
        $now = Carbon::now($tz);
        $bestHour = $profile->best_hour_local;

        if ($now->hour === $bestHour) {
            return $now;
        }

        $optimal = $now->copy()->setHour($bestHour)->setMinute(rand(0, 30));
        if ($optimal->isPast()) {
            $optimal->addDay();
        }

        return $optimal;
    }

    public function updateProfile(Chatter $chatter, Carbon $interactionTime): void
    {
        $profile = ChatterSendTimeProfile::firstOrCreate(
            ['chatter_id' => $chatter->id],
            ['interaction_heatmap' => array_fill(0, 24, 0)]
        );

        $localHour = $interactionTime->setTimezone($chatter->timezone ?: 'UTC')->hour;
        $heatmap = $profile->interaction_heatmap;
        $heatmap[$localHour] = ($heatmap[$localHour] ?? 0) + 1;

        $bestHour = array_keys($heatmap, max($heatmap))[0];
        $total = array_sum($heatmap);

        $profile->update([
            'interaction_heatmap' => $heatmap,
            'best_hour_local' => (int) $bestHour,
            'best_day_of_week' => $interactionTime->dayOfWeek,
            'sample_size' => $total,
            'confidence' => min(1.0, $total / 50),
        ]);
    }
}
