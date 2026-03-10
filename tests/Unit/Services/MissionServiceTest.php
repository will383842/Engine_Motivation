<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\MissionCompleted;
use App\Models\Chatter;
use App\Models\ChatterMission;
use App\Models\Mission;
use App\Services\MissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private MissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MissionService();
        Event::fake();
    }

    public function test_assign_daily_missions(): void
    {
        $chatter = Chatter::factory()->create();

        Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
            'target_count' => 3,
        ]);
        Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
            'target_count' => 1,
        ]);
        // Weekly mission should not be assigned
        Mission::factory()->create([
            'type' => 'weekly',
            'status' => 'active',
        ]);

        $this->service->assignDailyMissions($chatter);

        $assigned = ChatterMission::where('chatter_id', $chatter->id)->count();
        $this->assertEquals(2, $assigned);
    }

    public function test_assign_daily_missions_idempotent(): void
    {
        $chatter = Chatter::factory()->create();

        Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
        ]);

        $this->service->assignDailyMissions($chatter);
        $this->service->assignDailyMissions($chatter);

        $assigned = ChatterMission::where('chatter_id', $chatter->id)->count();
        $this->assertEquals(1, $assigned);
    }

    public function test_increment_progress_completes_mission(): void
    {
        $chatter = Chatter::factory()->create();

        $mission = Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
            'target_count' => 2,
            'xp_reward' => 100,
            'criteria' => ['trigger_event' => 'click'],
        ]);

        ChatterMission::create([
            'chatter_id' => $chatter->id,
            'mission_id' => $mission->id,
            'status' => 'assigned',
            'target_count' => 2,
            'progress_count' => 0,
            'expires_at' => now()->endOfDay(),
        ]);

        $this->service->incrementProgress($chatter, 'click', 1);
        $this->service->incrementProgress($chatter, 'click', 1);

        $cm = ChatterMission::where('chatter_id', $chatter->id)
            ->where('mission_id', $mission->id)
            ->first();

        $this->assertEquals('completed', $cm->status);
        $this->assertNotNull($cm->completed_at);
        $this->assertTrue($cm->reward_granted);

        Event::assertDispatched(MissionCompleted::class, function ($event) use ($chatter) {
            return $event->chatter->id === $chatter->id;
        });
    }

    public function test_non_matching_event_does_not_increment(): void
    {
        $chatter = Chatter::factory()->create();

        $mission = Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
            'target_count' => 1,
            'criteria' => ['trigger_event' => 'sale'],
        ]);

        ChatterMission::create([
            'chatter_id' => $chatter->id,
            'mission_id' => $mission->id,
            'status' => 'assigned',
            'target_count' => 1,
            'progress_count' => 0,
            'expires_at' => now()->endOfDay(),
        ]);

        $this->service->incrementProgress($chatter, 'click', 1);

        $cm = ChatterMission::where('chatter_id', $chatter->id)->first();
        $this->assertEquals(0, $cm->progress_count);
        $this->assertEquals('assigned', $cm->status);
    }

    public function test_increment_with_amount_greater_than_one(): void
    {
        $chatter = Chatter::factory()->create();

        $mission = Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
            'target_count' => 5,
            'xp_reward' => 50,
            'criteria' => ['trigger_event' => 'referral'],
        ]);

        ChatterMission::create([
            'chatter_id' => $chatter->id,
            'mission_id' => $mission->id,
            'status' => 'assigned',
            'target_count' => 5,
            'progress_count' => 0,
            'expires_at' => now()->endOfDay(),
        ]);

        $this->service->incrementProgress($chatter, 'referral', 3);

        $cm = ChatterMission::where('chatter_id', $chatter->id)->first();
        $this->assertEquals(3, $cm->progress_count);
        $this->assertEquals('in_progress', $cm->status);
    }

    public function test_already_completed_missions_not_incremented(): void
    {
        $chatter = Chatter::factory()->create();

        $mission = Mission::factory()->create([
            'type' => 'daily',
            'status' => 'active',
            'target_count' => 1,
            'criteria' => ['trigger_event' => 'click'],
        ]);

        ChatterMission::create([
            'chatter_id' => $chatter->id,
            'mission_id' => $mission->id,
            'status' => 'completed',
            'target_count' => 1,
            'progress_count' => 1,
            'completed_at' => now(),
            'reward_granted' => true,
            'expires_at' => now()->endOfDay(),
        ]);

        $this->service->incrementProgress($chatter, 'click', 1);

        // Should still be 1, not 2
        $cm = ChatterMission::where('chatter_id', $chatter->id)->first();
        $this->assertEquals(1, $cm->progress_count);
    }
}
