<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Chatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ActiveChattersWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeChatters = Chatter::where('lifecycle_state', 'active')
            ->where('is_active', true)
            ->count();

        $newThisWeek = Chatter::where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        $withStreak = Chatter::where('current_streak', '>', 0)->count();

        $averageLevel = round((float) Chatter::avg('level'), 1);

        return [
            Stat::make('Active Chatters', number_format($activeChatters))
                ->description('Lifecycle active & is_active')
                ->color('success'),
            Stat::make('New This Week', number_format($newThisWeek))
                ->description('Registered since ' . Carbon::now()->startOfWeek()->format('M d'))
                ->color('info'),
            Stat::make('With Streak', number_format($withStreak))
                ->description('Current streak > 0')
                ->color('warning'),
            Stat::make('Avg Level', (string) $averageLevel)
                ->description('Across all chatters')
                ->color('primary'),
        ];
    }
}
