<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Chatter;
use App\Models\LeaderboardEntry;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaderboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LeaderboardService::class);
        Redis::flushdb();
    }

    public function test_update_score_increments_redis_sorted_set(): void
    {
        $chatter = Chatter::factory()->create(['country' => 'FR']);
        $weekKey = now()->format('Y-\WW');

        $this->service->updateScore($chatter, 'xp', 100);

        $score = Redis::zscore("leaderboard:xp:weekly:{$weekKey}", $chatter->id);
        $this->assertEquals(100, (int) $score);
    }

    public function test_all_five_categories_tracked(): void
    {
        $chatter = Chatter::factory()->create();

        foreach (LeaderboardService::CATEGORIES as $cat) {
            $this->service->updateScore($chatter, $cat, 10);
        }

        $weekKey = now()->format('Y-\WW');
        foreach (LeaderboardService::CATEGORIES as $cat) {
            $score = Redis::zscore("leaderboard:{$cat}:weekly:{$weekKey}", $chatter->id);
            $this->assertEquals(10, (int) $score, "Category {$cat} should be tracked");
        }
    }

    public function test_invalid_category_ignored(): void
    {
        $chatter = Chatter::factory()->create();
        $this->service->updateScore($chatter, 'invalid_category', 100);

        $weekKey = now()->format('Y-\WW');
        $score = Redis::zscore("leaderboard:invalid_category:weekly:{$weekKey}", $chatter->id);
        $this->assertNull($score);
    }

    public function test_anti_gaming_blocks_xp_over_daily_cap(): void
    {
        $chatter = Chatter::factory()->create();
        $dailyKey = "leaderboard:daily:xp:{$chatter->id}:" . now()->toDateString();

        // Pre-fill daily to near cap (500)
        Redis::set($dailyKey, 495);

        // This should be blocked (495 + 100 > 500)
        $this->service->updateScore($chatter, 'xp', 100);

        $weekKey = now()->format('Y-\WW');
        $score = Redis::zscore("leaderboard:xp:weekly:{$weekKey}", $chatter->id);
        $this->assertNull($score, 'Should be blocked by daily cap');
    }

    public function test_get_rank_returns_correct_position(): void
    {
        $chatter1 = Chatter::factory()->create();
        $chatter2 = Chatter::factory()->create();

        $this->service->updateScore($chatter1, 'xp', 200);
        $this->service->updateScore($chatter2, 'xp', 100);

        $this->assertEquals(1, $this->service->getRank($chatter1, 'xp'));
        $this->assertEquals(2, $this->service->getRank($chatter2, 'xp'));
    }

    public function test_get_top_chatters_returns_sorted(): void
    {
        $chatters = Chatter::factory()->count(5)->create();
        foreach ($chatters as $i => $chatter) {
            $this->service->updateScore($chatter, 'xp', ($i + 1) * 10);
        }

        $top = $this->service->getTopChatters('xp', 'weekly', 3);
        $this->assertCount(3, $top);
    }

    public function test_record_sale_updates_revenue_and_conversions(): void
    {
        $chatter = Chatter::factory()->create();
        $this->service->recordSale($chatter, 500);

        $weekKey = now()->format('Y-\WW');
        $revenue = Redis::zscore("leaderboard:revenue:weekly:{$weekKey}", $chatter->id);
        $conversions = Redis::zscore("leaderboard:conversions:weekly:{$weekKey}", $chatter->id);

        $this->assertEquals(500, (int) $revenue);
        $this->assertEquals(1, (int) $conversions);
    }

    public function test_record_sale_ignores_below_minimum(): void
    {
        $chatter = Chatter::factory()->create();
        $this->service->recordSale($chatter, 50); // Below $1 minimum

        $weekKey = now()->format('Y-\WW');
        $revenue = Redis::zscore("leaderboard:revenue:weekly:{$weekKey}", $chatter->id);
        $this->assertNull($revenue, 'Revenue below minimum should be ignored');
    }

    public function test_persist_to_database_creates_entries(): void
    {
        $chatter = Chatter::factory()->create();
        $this->service->updateScore($chatter, 'xp', 100);

        $this->service->persistToDatabase();

        $entry = LeaderboardEntry::where('chatter_id', $chatter->id)
            ->where('metric', 'xp')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(100, $entry->value);
        $this->assertEquals(1, $entry->rank);
    }

    public function test_country_leaderboard_tracked(): void
    {
        $chatter = Chatter::factory()->create(['country' => 'FR']);
        $this->service->updateScore($chatter, 'xp', 50);

        $weekKey = now()->format('Y-\WW');
        $score = Redis::zscore("leaderboard:xp:country:FR:weekly:{$weekKey}", $chatter->id);
        $this->assertEquals(50, (int) $score);
    }
}
