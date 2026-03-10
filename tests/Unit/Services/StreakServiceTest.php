<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\StreakBroken;
use App\Events\StreakMilestone;
use App\Models\Chatter;
use App\Models\RewardsLedger;
use App\Models\Streak;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StreakServiceTest extends TestCase
{
    use RefreshDatabase;

    private StreakService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StreakService();
        Event::fake();
        Redis::flushall();
    }

    // ─── recordActivity ───

    public function test_record_activity_creates_new_streak(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 0, 'timezone' => 'UTC']);

        $this->service->recordActivity($chatter);

        $chatter->refresh();
        $this->assertEquals(1, $chatter->current_streak);
        $this->assertDatabaseHas('streaks', [
            'chatter_id' => $chatter->id,
            'current_count' => 1,
        ]);
    }

    public function test_record_activity_increments_consecutive_streak(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 5, 'timezone' => 'UTC']);
        $yesterday = Carbon::yesterday('UTC')->toDateString();

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 5,
            'longest_count' => 5,
            'last_activity_date' => $yesterday,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $this->service->recordActivity($chatter);

        $chatter->refresh();
        $this->assertEquals(6, $chatter->current_streak);
    }

    public function test_record_activity_idempotent_same_day(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 3, 'timezone' => 'UTC']);
        $today = Carbon::now('UTC')->toDateString();

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 3,
            'longest_count' => 3,
            'last_activity_date' => $today,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $this->service->recordActivity($chatter);

        $chatter->refresh();
        $this->assertEquals(3, $chatter->current_streak);
    }

    public function test_record_activity_breaks_streak_on_gap(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 10, 'timezone' => 'UTC']);
        $twoDaysAgo = Carbon::now('UTC')->subDays(2)->toDateString();

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 10,
            'longest_count' => 10,
            'last_activity_date' => $twoDaysAgo,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $this->service->recordActivity($chatter);

        $chatter->refresh();
        $this->assertEquals(1, $chatter->current_streak);
        Event::assertDispatched(StreakBroken::class);
    }

    public function test_milestone_event_dispatched_at_day_7(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 6, 'timezone' => 'UTC']);
        $yesterday = Carbon::yesterday('UTC')->toDateString();

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 6,
            'longest_count' => 6,
            'last_activity_date' => $yesterday,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $this->service->recordActivity($chatter);

        Event::assertDispatched(StreakMilestone::class, function ($event) use ($chatter) {
            return $event->chatter->id === $chatter->id;
        });
    }

    // ─── checkAndBreakInactive ───

    public function test_check_and_break_inactive_breaks_stale_streak(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 5, 'timezone' => 'UTC']);
        $twoDaysAgo = Carbon::now('UTC')->subDays(2)->toDateString();

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 5,
            'longest_count' => 5,
            'last_activity_date' => $twoDaysAgo,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $this->service->checkAndBreakInactive($chatter);

        $chatter->refresh();
        $this->assertEquals(0, $chatter->current_streak);
    }

    public function test_frozen_streak_is_protected(): void
    {
        $chatter = Chatter::factory()->create(['current_streak' => 5, 'timezone' => 'UTC']);
        $twoDaysAgo = Carbon::now('UTC')->subDays(2)->toDateString();
        $tomorrow = Carbon::tomorrow('UTC')->toDateString();

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 5,
            'longest_count' => 5,
            'last_activity_date' => $twoDaysAgo,
            'streak_frozen_until' => $tomorrow,
            'freeze_count_used' => 1,
            'freeze_count_max' => 1,
        ]);

        $this->service->checkAndBreakInactive($chatter);

        $chatter->refresh();
        $this->assertEquals(5, $chatter->current_streak);
    }

    // ─── Multiplier tiers ───

    public function test_multiplier_tiers(): void
    {
        $this->assertEquals(1.00, $this->service->getMultiplier(1));
        $this->assertEquals(1.00, $this->service->getMultiplier(6));
        $this->assertEquals(1.05, $this->service->getMultiplier(7));
        $this->assertEquals(1.05, $this->service->getMultiplier(13));
        $this->assertEquals(1.10, $this->service->getMultiplier(14));
        $this->assertEquals(1.10, $this->service->getMultiplier(29));
        $this->assertEquals(1.20, $this->service->getMultiplier(30));
        $this->assertEquals(1.20, $this->service->getMultiplier(99));
        $this->assertEquals(1.50, $this->service->getMultiplier(100));
        $this->assertEquals(1.50, $this->service->getMultiplier(365));
    }

    // ─── purchaseFreeze ───

    public function test_purchase_freeze_success(): void
    {
        $chatter = Chatter::factory()->create(['balance_cents' => 500]);

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 5,
            'longest_count' => 5,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $result = $this->service->purchaseFreeze($chatter);

        $this->assertTrue($result);
        $chatter->refresh();
        $this->assertEquals(300, $chatter->balance_cents); // 500 - 200
        $this->assertDatabaseHas('rewards_ledgers', [
            'chatter_id' => $chatter->id,
            'reward_type' => 'streak_freeze',
            'amount' => -200,
        ]);
    }

    public function test_purchase_freeze_insufficient_balance(): void
    {
        $chatter = Chatter::factory()->create(['balance_cents' => 100]);

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 5,
            'longest_count' => 5,
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $result = $this->service->purchaseFreeze($chatter);

        $this->assertFalse($result);
        $chatter->refresh();
        $this->assertEquals(100, $chatter->balance_cents);
    }

    // ─── rescueStreak ───

    public function test_rescue_streak_within_48h(): void
    {
        $chatter = Chatter::factory()->create([
            'balance_cents' => 1000,
            'current_streak' => 0,
        ]);

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 0,
            'longest_count' => 15,
            'previous_count' => 15,
            'broken_at' => now()->subHours(24),
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $result = $this->service->rescueStreak($chatter);

        $this->assertTrue($result);
        $chatter->refresh();
        $this->assertEquals(500, $chatter->balance_cents); // 1000 - 500
        $this->assertEquals(15, $chatter->current_streak);
    }

    public function test_rescue_streak_expired_after_48h(): void
    {
        $chatter = Chatter::factory()->create([
            'balance_cents' => 1000,
            'current_streak' => 0,
        ]);

        Streak::create([
            'chatter_id' => $chatter->id,
            'current_count' => 0,
            'longest_count' => 15,
            'previous_count' => 15,
            'broken_at' => now()->subHours(49),
            'freeze_count_used' => 0,
            'freeze_count_max' => 0,
        ]);

        $result = $this->service->rescueStreak($chatter);

        $this->assertFalse($result);
        $chatter->refresh();
        $this->assertEquals(1000, $chatter->balance_cents);
    }
}
