<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Chatter;
use App\Services\LeaderboardService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class LeaderboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static string $view = 'filament.pages.leaderboard';
    protected static ?string $title = 'Live Leaderboard';
    protected static ?string $navigationGroup = 'Gamification';

    public string $period = 'weekly';
    public string $category = 'xp';

    protected function getViewData(): array
    {
        $leaderboardService = app(LeaderboardService::class);
        $topChatters = $leaderboardService->getTopChatters($this->category, $this->period, 50);

        $entries = collect();

        if (!empty($topChatters)) {
            $chatterIds = array_keys($topChatters);
            $chatters = Chatter::query()
                ->whereIn('id', $chatterIds)
                ->get()
                ->keyBy('id');

            $rank = 0;
            foreach ($topChatters as $chatterId => $score) {
                $rank++;
                $chatter = $chatters->get($chatterId);
                if (!$chatter) {
                    continue;
                }

                $entries->push([
                    'rank' => $rank,
                    'display_name' => $chatter->display_name ?? 'Unknown',
                    'country' => $chatter->country ?? '-',
                    'level' => $chatter->level ?? 0,
                    'score' => (int) $score,
                    'total_xp' => $chatter->total_xp ?? 0,
                    'current_streak' => $chatter->current_streak ?? 0,
                ]);
            }
        }

        return [
            'entries' => $entries,
            'period' => $this->period,
            'category' => $this->category,
            'categories' => LeaderboardService::CATEGORIES,
        ];
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }
}
