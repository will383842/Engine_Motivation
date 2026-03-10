<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Chatter;
use App\Models\WhatsAppNumber;
use App\Services\WhatsAppCircuitBreaker;
use App\Services\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class WhatsAppSenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    public function test_service_class_exists(): void
    {
        $this->assertTrue(class_exists(WhatsAppSender::class));
    }

    public function test_returns_null_when_no_whatsapp_phone(): void
    {
        $chatter = Chatter::factory()->create(['whatsapp_phone' => null]);
        $sender = app(WhatsAppSender::class);
        $this->assertNull($sender->send($chatter, 'welcome'));
    }

    public function test_returns_null_when_telegram_linked(): void
    {
        $chatter = Chatter::factory()->create([
            'telegram_id' => '12345',
            'whatsapp_phone' => '+33612345678',
        ]);
        $sender = app(WhatsAppSender::class);
        $this->assertNull($sender->send($chatter, 'welcome'));
    }

    public function test_warmup_schedule_has_8_weeks(): void
    {
        // Verify via reflection that WARMUP_SCHEDULE has 8 entries
        $reflection = new \ReflectionClass(WhatsAppSender::class);
        $constant = $reflection->getReflectionConstant('WARMUP_SCHEDULE');
        $schedule = $constant->getValue();

        $this->assertCount(8, $schedule);
        $this->assertEquals(50, $schedule[1]);    // Week 1: 50/day
        $this->assertEquals(1500, $schedule[8]);  // Week 8: 1500/day
    }

    public function test_monthly_budget_cap_is_300_dollars(): void
    {
        $reflection = new \ReflectionClass(WhatsAppSender::class);
        $constant = $reflection->getReflectionConstant('MONTHLY_BUDGET_CAP_CENTS');
        $this->assertEquals(30000, $constant->getValue());
    }

    public function test_max_emojis_is_3(): void
    {
        $reflection = new \ReflectionClass(WhatsAppSender::class);
        $constant = $reflection->getReflectionConstant('MAX_EMOJIS');
        $this->assertEquals(3, $constant->getValue());
    }

    public function test_circuit_breaker_records_success(): void
    {
        $number = WhatsAppNumber::factory()->create(['is_active' => true]);
        $cb = new WhatsAppCircuitBreaker();

        $cb->recordSuccess($number);

        $sent = Redis::get("wa_num:{$number->id}:sent_today");
        $this->assertEquals(1, (int) $sent);
    }

    public function test_circuit_breaker_state_transitions(): void
    {
        $number = WhatsAppNumber::factory()->create(['is_active' => true]);
        $cb = new WhatsAppCircuitBreaker();

        $this->assertTrue($cb->canSend($number));
        $this->assertEquals('open', $cb->getState($number));

        $cb->setState($number, 'pause');
        $this->assertFalse($cb->canSend($number));

        $cb->setState($number, 'reduce');
        $this->assertTrue($cb->canSend($number), 'Reduce state should still allow sending');
    }

    public function test_circuit_breaker_recovery(): void
    {
        $number = WhatsAppNumber::factory()->create(['is_active' => true]);
        $cb = new WhatsAppCircuitBreaker();

        $cb->setState($number, 'pause');

        // Simulate cooldown expired
        Redis::set("wa_num:{$number->id}:circuit_breaker:until", now()->subMinute()->timestamp);

        $cb->checkAndRecover($number);
        $this->assertEquals('open', $cb->getState($number));
    }
}
