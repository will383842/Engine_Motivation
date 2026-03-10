<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Chatter;
use App\Models\ChatterFatigueScore;
use App\Services\FatigueScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FatigueScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private FatigueScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FatigueScoreService();
    }

    public function test_no_fatigue_score_returns_full_multiplier(): void
    {
        $chatter = Chatter::factory()->create();

        $multiplier = $this->service->getMultiplier($chatter, 'telegram');

        $this->assertEquals(1.0, $multiplier);
    }

    public function test_low_fatigue_returns_full_multiplier(): void
    {
        $chatter = Chatter::factory()->create();

        ChatterFatigueScore::create([
            'chatter_id' => $chatter->id,
            'channel' => 'telegram',
            'fatigue_score' => 15,
            'frequency_multiplier' => 1.0,
        ]);

        $multiplier = $this->service->getMultiplier($chatter, 'telegram');

        $this->assertEquals(1.0, $multiplier);
    }

    public function test_medium_fatigue_returns_75_percent(): void
    {
        $chatter = Chatter::factory()->create();

        ChatterFatigueScore::create([
            'chatter_id' => $chatter->id,
            'channel' => 'whatsapp',
            'fatigue_score' => 35,
            'frequency_multiplier' => 0.75,
        ]);

        $multiplier = $this->service->getMultiplier($chatter, 'whatsapp');

        $this->assertEquals(0.75, $multiplier);
    }

    public function test_high_fatigue_returns_50_percent(): void
    {
        $chatter = Chatter::factory()->create();

        ChatterFatigueScore::create([
            'chatter_id' => $chatter->id,
            'channel' => 'telegram',
            'fatigue_score' => 55,
            'frequency_multiplier' => 0.50,
        ]);

        $multiplier = $this->service->getMultiplier($chatter, 'telegram');

        $this->assertEquals(0.50, $multiplier);
    }

    public function test_extreme_fatigue_returns_zero(): void
    {
        $chatter = Chatter::factory()->create();

        ChatterFatigueScore::create([
            'chatter_id' => $chatter->id,
            'channel' => 'telegram',
            'fatigue_score' => 90,
            'frequency_multiplier' => 0.0,
        ]);

        $multiplier = $this->service->getMultiplier($chatter, 'telegram');

        $this->assertEquals(0.0, $multiplier);
    }

    public function test_channel_isolation(): void
    {
        $chatter = Chatter::factory()->create();

        ChatterFatigueScore::create([
            'chatter_id' => $chatter->id,
            'channel' => 'whatsapp',
            'fatigue_score' => 90,
            'frequency_multiplier' => 0.0,
        ]);

        // Telegram should be unaffected
        $multiplier = $this->service->getMultiplier($chatter, 'telegram');

        $this->assertEquals(1.0, $multiplier);
    }

    public function test_calculate_returns_capped_at_100(): void
    {
        $chatter = Chatter::factory()->create();

        // Even with maxed factors, score caps at 100
        $score = $this->service->calculate($chatter, 'telegram');

        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }
}
