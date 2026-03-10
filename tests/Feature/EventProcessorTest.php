<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chatter;
use App\Models\ChatterEvent;
use App\Models\WebhookEvent;
use App\Services\EventProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventProcessorTest extends TestCase
{
    use RefreshDatabase;

    private EventProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = app(EventProcessor::class);
    }

    public function test_processes_chatter_registered_event(): void
    {
        $event = WebhookEvent::create([
            'event_type' => 'chatter.registered',
            'external_event_id' => 'test-001',
            'payload' => [
                'uid' => 'firebase-uid-001',
                'email' => 'test@example.com',
                'displayName' => 'Test User',
                'language' => 'fr',
                'country' => 'FR',
            ],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        Event::fake();
        $this->processor->process($event);

        $event->refresh();
        $this->assertEquals('processed', $event->status);

        $chatter = Chatter::where('firebase_uid', 'firebase-uid-001')->first();
        $this->assertNotNull($chatter);
        $this->assertEquals('Test User', $chatter->display_name);
    }

    public function test_processes_sale_completed_event(): void
    {
        $chatter = Chatter::factory()->create([
            'firebase_uid' => 'uid-sale',
            'balance_cents' => 0,
            'lifetime_earnings_cents' => 0,
            'total_sales' => 0,
        ]);

        $event = WebhookEvent::create([
            'event_type' => 'chatter.sale_completed',
            'external_event_id' => 'test-sale-001',
            'payload' => [
                'uid' => 'uid-sale',
                'commissionCents' => 1000,
                'callId' => 'call-123',
            ],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        Event::fake();
        $this->processor->process($event);

        $chatter->refresh();
        $this->assertEquals(1000, $chatter->balance_cents);
        $this->assertEquals(1000, $chatter->lifetime_earnings_cents);
        $this->assertEquals(1, $chatter->total_sales);
    }

    public function test_referral_signup_records_streak_activity(): void
    {
        $chatter = Chatter::factory()->create(['firebase_uid' => 'uid-referral']);

        $event = WebhookEvent::create([
            'event_type' => 'chatter.referral_signup',
            'external_event_id' => 'test-ref-001',
            'payload' => ['uid' => 'uid-referral', 'referredUid' => 'new-uid'],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        Event::fake();
        $this->processor->process($event);

        $chatter->refresh();
        $this->assertNotNull($chatter->last_active_at);
    }

    public function test_referral_activated_records_streak_and_bonus(): void
    {
        $chatter = Chatter::factory()->create([
            'firebase_uid' => 'uid-ref-act',
            'balance_cents' => 0,
        ]);

        $event = WebhookEvent::create([
            'event_type' => 'chatter.referral_activated',
            'external_event_id' => 'test-ref-act-001',
            'payload' => [
                'uid' => 'uid-ref-act',
                'activationBonusCents' => 500,
            ],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        Event::fake();
        $this->processor->process($event);

        $chatter->refresh();
        $this->assertEquals(500, $chatter->balance_cents);
        $this->assertNotNull($chatter->last_active_at);
    }

    public function test_orphan_event_retries_up_to_5_times(): void
    {
        $event = WebhookEvent::create([
            'event_type' => 'chatter.sale_completed',
            'external_event_id' => 'test-orphan',
            'payload' => ['uid' => 'nonexistent-uid', 'commissionCents' => 100],
            'status' => 'pending',
            'attempts' => 3,
        ]);

        $this->processor->process($event);

        $event->refresh();
        $this->assertEquals('pending', $event->status);
        $this->assertEquals(4, $event->attempts);
    }

    public function test_orphan_event_skipped_after_5_attempts(): void
    {
        $event = WebhookEvent::create([
            'event_type' => 'chatter.sale_completed',
            'external_event_id' => 'test-orphan-5',
            'payload' => ['uid' => 'nonexistent-uid', 'commissionCents' => 100],
            'status' => 'pending',
            'attempts' => 4,
        ]);

        $this->processor->process($event);

        $event->refresh();
        $this->assertEquals('skipped', $event->status);
    }

    public function test_creates_chatter_event_record(): void
    {
        $chatter = Chatter::factory()->create(['firebase_uid' => 'uid-event']);

        $event = WebhookEvent::create([
            'event_type' => 'chatter.click_tracked',
            'external_event_id' => 'test-click-001',
            'payload' => ['uid' => 'uid-event'],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        Event::fake();
        $this->processor->process($event);

        $chatterEvent = ChatterEvent::where('chatter_id', $chatter->id)
            ->where('event_type', 'chatter.click_tracked')
            ->first();

        $this->assertNotNull($chatterEvent);
    }

    public function test_gdpr_delete_anonymizes_chatter(): void
    {
        $chatter = Chatter::factory()->create([
            'firebase_uid' => 'uid-delete',
            'email' => 'test@example.com',
            'display_name' => 'John Doe',
        ]);

        $event = WebhookEvent::create([
            'event_type' => 'chatter.deleted',
            'external_event_id' => 'test-delete-001',
            'payload' => ['uid' => 'uid-delete'],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $this->processor->process($event);

        $chatter->refresh();
        $this->assertEquals('Anonymized User', $chatter->display_name);
        $this->assertFalse($chatter->is_active);
        $this->assertEquals('sunset', $chatter->lifecycle_state);
    }
}
