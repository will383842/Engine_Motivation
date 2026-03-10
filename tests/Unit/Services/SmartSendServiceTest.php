<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Chatter;
use App\Models\ChatterSendTimeProfile;
use App\Services\SmartSendService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartSendServiceTest extends TestCase
{
    use RefreshDatabase;

    private SmartSendService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmartSendService();
    }

    public function test_returns_10h_default_when_no_profile(): void
    {
        $chatter = Chatter::factory()->create(['timezone' => 'UTC']);

        $result = $this->service->getOptimalSendTime($chatter);

        $this->assertNotNull($result, 'Should return 10h default, not null');
        $this->assertEquals(10, $result->hour);
    }

    public function test_returns_10h_default_when_sample_too_small(): void
    {
        $chatter = Chatter::factory()->create(['timezone' => 'Europe/Paris']);

        ChatterSendTimeProfile::create([
            'chatter_id' => $chatter->id,
            'best_hour_local' => 14,
            'sample_size' => 3, // < 5 threshold
            'confidence' => 0.1,
            'interaction_heatmap' => array_fill(0, 24, 0),
        ]);

        $result = $this->service->getOptimalSendTime($chatter);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result->hour);
    }

    public function test_returns_optimal_hour_when_enough_data(): void
    {
        $chatter = Chatter::factory()->create(['timezone' => 'UTC']);

        ChatterSendTimeProfile::create([
            'chatter_id' => $chatter->id,
            'best_hour_local' => 14,
            'sample_size' => 20,
            'confidence' => 0.6,
            'interaction_heatmap' => array_fill(0, 24, 1),
        ]);

        $result = $this->service->getOptimalSendTime($chatter);

        $this->assertNotNull($result);
        $this->assertEquals(14, $result->hour);
    }

    public function test_optimal_time_in_future(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 16:00:00'));

        $chatter = Chatter::factory()->create(['timezone' => 'UTC']);

        ChatterSendTimeProfile::create([
            'chatter_id' => $chatter->id,
            'best_hour_local' => 10, // Already past (16:00 now)
            'sample_size' => 50,
            'confidence' => 0.8,
            'interaction_heatmap' => array_fill(0, 24, 2),
        ]);

        $result = $this->service->getOptimalSendTime($chatter);

        $this->assertNotNull($result);
        $this->assertTrue($result->isFuture(), 'Should return tomorrow 10h since 10h today has passed');
        $this->assertEquals(10, $result->hour);

        Carbon::setTestNow();
    }

    public function test_update_profile_tracks_interaction(): void
    {
        $chatter = Chatter::factory()->create(['timezone' => 'UTC']);
        $time = Carbon::parse('2026-03-09 14:30:00');

        $this->service->updateProfile($chatter, $time);

        $profile = ChatterSendTimeProfile::where('chatter_id', $chatter->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(14, $profile->best_hour_local);
        $this->assertEquals(1, $profile->sample_size);
    }

    public function test_update_profile_accumulates_heatmap(): void
    {
        $chatter = Chatter::factory()->create(['timezone' => 'UTC']);

        // Multiple interactions at different hours
        $this->service->updateProfile($chatter, Carbon::parse('2026-03-09 10:00'));
        $this->service->updateProfile($chatter, Carbon::parse('2026-03-09 10:30'));
        $this->service->updateProfile($chatter, Carbon::parse('2026-03-09 14:00'));

        $profile = ChatterSendTimeProfile::where('chatter_id', $chatter->id)->first();
        $this->assertEquals(10, $profile->best_hour_local, 'Hour 10 has 2 interactions vs 1 for hour 14');
        $this->assertEquals(3, $profile->sample_size);
    }

    public function test_default_fallback_respects_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 08:00:00 UTC'));

        $chatter = Chatter::factory()->create(['timezone' => 'America/New_York']); // UTC-5

        $result = $this->service->getOptimalSendTime($chatter);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result->hour);
        $this->assertEquals('America/New_York', $result->timezone->getName());

        Carbon::setTestNow();
    }
}
