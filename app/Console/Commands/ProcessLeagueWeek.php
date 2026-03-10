<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\LeagueTier;
use App\Models\Chatter;
use App\Models\League;
use App\Models\LeagueParticipant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessLeagueWeek extends Command
{
    protected $signature = 'leagues:process {--dry-run : Show results without applying}';
    protected $description = 'Process weekly league promotions and relegations';

    private const MAX_PARTICIPANTS = 30;
    private const PROMOTION_COUNT = 5;
    private const RELEGATION_COUNT = 5;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $lastWeekKey = now()->subWeek()->format('Y-W');
        $newWeekKey = now()->format('Y-W');

        $this->info("Processing leagues for week {$lastWeekKey}" . ($dryRun ? ' (DRY RUN)' : ''));

        // Step 1: Process results from last week's leagues
        $lastWeekLeagues = League::where('week_key', $lastWeekKey)->with('participants.chatter')->get();

        $promoted = [];
        $relegated = [];

        foreach ($lastWeekLeagues as $league) {
            $ranked = $league->participants->sortByDesc('weekly_xp')->values();

            foreach ($ranked as $i => $participant) {
                $rank = $i + 1;
                $isPromoted = $rank <= self::PROMOTION_COUNT && $league->tier !== LeagueTier::Legend->value;
                $isRelegated = $rank > ($ranked->count() - self::RELEGATION_COUNT) && $league->tier !== LeagueTier::Bronze->value;

                if (!$dryRun) {
                    $participant->update([
                        'rank' => $rank,
                        'promoted' => $isPromoted,
                        'relegated' => $isRelegated,
                    ]);
                }

                if ($isPromoted) {
                    $promoted[$participant->chatter_id] = $this->getNextTier($league->tier);
                } elseif ($isRelegated) {
                    $relegated[$participant->chatter_id] = $this->getPreviousTier($league->tier);
                }
            }

            $this->line("  {$league->tier} league: {$ranked->count()} participants, " .
                count(array_filter($promoted, fn ($t) => true)) . " promoted, " .
                count(array_filter($relegated, fn ($t) => true)) . " relegated");
        }

        if ($dryRun) {
            $this->info("DRY RUN — no changes applied. Promoted: " . count($promoted) . ", Relegated: " . count($relegated));
            return Command::SUCCESS;
        }

        // Step 2: Determine new tier for each active chatter
        $activeChatters = Chatter::where('is_active', true)
            ->where('lifecycle_state', 'active')
            ->orderByDesc('total_xp')
            ->get();

        // Build tier assignments: promoted/relegated override, others stay or start at bronze
        $tierAssignments = [];
        foreach ($activeChatters as $chatter) {
            if (isset($promoted[$chatter->id])) {
                $tierAssignments[$chatter->id] = $promoted[$chatter->id];
            } elseif (isset($relegated[$chatter->id])) {
                $tierAssignments[$chatter->id] = $relegated[$chatter->id];
            } else {
                $tierAssignments[$chatter->id] = $chatter->league_tier ?? LeagueTier::Bronze->value;
            }
        }

        // Step 3: Create new week's leagues and assign participants in groups of 30
        $tiers = collect(LeagueTier::cases())->pluck('value');
        $totalAssigned = 0;

        foreach ($tiers as $tier) {
            $chatterIds = collect($tierAssignments)
                ->filter(fn ($t) => $t === $tier)
                ->keys()
                ->shuffle(); // Randomize within tier for fair grouping

            foreach ($chatterIds->chunk(self::MAX_PARTICIPANTS) as $chunk) {
                $league = League::create([
                    'tier' => $tier,
                    'week_key' => $newWeekKey,
                    'max_participants' => self::MAX_PARTICIPANTS,
                    'promotion_count' => self::PROMOTION_COUNT,
                    'relegation_count' => self::RELEGATION_COUNT,
                ]);

                foreach ($chunk as $chatterId) {
                    LeagueParticipant::create([
                        'league_id' => $league->id,
                        'chatter_id' => $chatterId,
                        'weekly_xp' => 0,
                        'rank' => 0,
                        'promoted' => false,
                        'relegated' => false,
                    ]);
                    $totalAssigned++;
                }

                // Update chatter's league_tier
                Chatter::whereIn('id', $chunk->toArray())->update(['league_tier' => $tier]);
            }
        }

        $this->info("League processing complete: {$totalAssigned} chatters assigned to new week {$newWeekKey}");
        Log::info('ProcessLeagueWeek completed', [
            'week' => $newWeekKey,
            'totalAssigned' => $totalAssigned,
            'promoted' => count($promoted),
            'relegated' => count($relegated),
        ]);

        return Command::SUCCESS;
    }

    private function getNextTier(string $tier): string
    {
        $tiers = array_map(fn ($t) => $t->value, LeagueTier::cases());
        $index = array_search($tier, $tiers);
        return $tiers[min($index + 1, count($tiers) - 1)];
    }

    private function getPreviousTier(string $tier): string
    {
        $tiers = array_map(fn ($t) => $t->value, LeagueTier::cases());
        $index = array_search($tier, $tiers);
        return $tiers[max($index - 1, 0)];
    }
}
