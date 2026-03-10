<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\WhatsAppNumber;
use App\Services\WhatsAppCircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class WhatsAppCircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppCircuitBreaker $breaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->breaker = new WhatsAppCircuitBreaker();
        Redis::flushall();

        Config::set('whatsapp.circuit_breaker', [
            'yellow_threshold' => 0.05,
            'red_threshold' => 0.10,
            'critical_threshold' => 0.25,
            'cool_down_minutes' => 60,
        ]);
    }

    private function createNumber(): WhatsAppNumber
    {
        return WhatsAppNumber::create([
            'phone_number' => '+1' . fake()->numerify('##########'),
            'is_active' => true,
        ]);
    }

    public function test_can_send_in_open_state(): void
    {
        $number = $this->createNumber();

        $this->assertTrue($this->breaker->canSend($number));
    }

    public function test_can_send_in_reduce_state(): void
    {
        $number = $this->createNumber();
        $this->breaker->setState($number, 'reduce');

        $this->assertTrue($this->breaker->canSend($number));
    }

    public function test_cannot_send_in_pause_state(): void
    {
        $number = $this->createNumber();
        $this->breaker->setState($number, 'pause');

        $this->assertFalse($this->breaker->canSend($number));
    }

    public function test_cannot_send_in_stop_state(): void
    {
        $number = $this->createNumber();
        $this->breaker->setState($number, 'stop');

        $this->assertFalse($this->breaker->canSend($number));
    }

    public function test_record_success_increments_sent_counter(): void
    {
        $number = $this->createNumber();

        $this->breaker->recordSuccess($number);
        $this->breaker->recordSuccess($number);

        $sent = (int) Redis::get("wa_num:{$number->id}:sent_today");
        $this->assertEquals(2, $sent);
    }

    public function test_record_success_clears_consecutive_failures(): void
    {
        $number = $this->createNumber();
        Redis::set("wa_num:{$number->id}:consecutive_failures", 3);

        $this->breaker->recordSuccess($number);

        $failures = (int) Redis::get("wa_num:{$number->id}:consecutive_failures");
        $this->assertEquals(0, $failures);
    }

    public function test_record_failure_transitions_to_reduce(): void
    {
        $number = $this->createNumber();

        // Seed some sent messages so block rate can be calculated
        Redis::set("wa_num:{$number->id}:sent_today", 10);

        // 3 consecutive failures → reduce
        $this->breaker->recordFailure($number, 21211);
        $this->breaker->recordFailure($number, 21211);
        $this->breaker->recordFailure($number, 21211);

        $state = $this->breaker->getState($number);
        $this->assertContains($state, ['reduce', 'pause', 'stop']);
    }

    public function test_check_and_recover_after_cooldown(): void
    {
        $number = $this->createNumber();
        $this->breaker->setState($number, 'pause');

        // Manually set cooldown to past
        Redis::set("wa_num:{$number->id}:circuit_breaker:until", now()->subMinutes(5)->timestamp);

        $this->breaker->checkAndRecover($number);

        $this->assertEquals('open', $this->breaker->getState($number));
    }

    public function test_state_persists_to_database(): void
    {
        $number = $this->createNumber();

        $this->breaker->setState($number, 'pause');

        $number->refresh();
        $this->assertEquals('pause', $number->circuit_breaker_state);
        $this->assertNotNull($number->circuit_breaker_until);
    }
}
