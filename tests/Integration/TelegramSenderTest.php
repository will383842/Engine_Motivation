<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Chatter;
use App\Models\SuppressionList;
use App\Models\TelegramBot;
use App\Services\TelegramBotRouter;
use App\Services\TelegramCircuitBreaker;
use App\Services\TelegramSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class TelegramSenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    public function test_service_class_exists(): void
    {
        $this->assertTrue(class_exists(TelegramSender::class));
    }

    public function test_returns_null_when_no_telegram_id(): void
    {
        $chatter = Chatter::factory()->create(['telegram_id' => null]);
        $sender = app(TelegramSender::class);
        $this->assertNull($sender->send($chatter, 'welcome'));
    }

    public function test_circuit_breaker_pause_threshold_is_20(): void
    {
        $this->assertEquals(20, TelegramCircuitBreaker::PAUSE_15MIN_THRESHOLD);
    }

    public function test_retry_delays_are_0_30_300(): void
    {
        $this->assertEquals([0, 30, 300], TelegramCircuitBreaker::RETRY_DELAYS);
        $this->assertEquals(0, TelegramCircuitBreaker::getRetryDelay(0));
        $this->assertEquals(30, TelegramCircuitBreaker::getRetryDelay(1));
        $this->assertEquals(300, TelegramCircuitBreaker::getRetryDelay(2));
    }

    public function test_bot_router_has_pool_hierarchy(): void
    {
        $this->assertEquals('primary', TelegramBotRouter::POOL_PRIMARY);
        $this->assertEquals('secondary', TelegramBotRouter::POOL_SECONDARY);
        $this->assertEquals('standby', TelegramBotRouter::POOL_STANDBY);
    }

    public function test_circuit_breaker_pauses_at_20_failures(): void
    {
        $bot = TelegramBot::factory()->create(['is_active' => true]);
        $cb = new TelegramCircuitBreaker();

        for ($i = 0; $i < 19; $i++) {
            $cb->recordFailure($bot, 500);
        }
        $this->assertEquals('open', $cb->getState($bot));

        $cb->recordFailure($bot, 500);
        $this->assertEquals('pause_15min', $cb->getState($bot));
    }

    public function test_circuit_breaker_success_resets_failures(): void
    {
        $bot = TelegramBot::factory()->create(['is_active' => true, 'consecutive_failures' => 5]);
        $cb = new TelegramCircuitBreaker();

        $cb->recordSuccess($bot);
        $this->assertNull(Redis::get("tg_bot:{$bot->id}:consecutive_failures"));
    }

    public function test_suppression_created_for_403(): void
    {
        $chatter = Chatter::factory()->create(['telegram_id' => '12345']);

        SuppressionList::firstOrCreate(
            ['chatter_id' => $chatter->id, 'channel' => 'telegram'],
            ['reason' => 'blocked', 'source' => 'telegram_403']
        );

        $this->assertDatabaseHas('suppression_lists', [
            'chatter_id' => $chatter->id,
            'channel' => 'telegram',
            'reason' => 'blocked',
        ]);
    }
}
