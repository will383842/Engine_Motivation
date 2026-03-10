<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.webhook_secret', $this->secret);
        Queue::fake();
    }

    private function signPayload(string $payload, ?int $timestamp = null): array
    {
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $this->secret);

        return [
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string) $timestamp,
        ];
    }

    public function test_valid_webhook_returns_202(): void
    {
        $payload = json_encode([
            'event_type' => 'chatter.registered',
            'event_id' => 'evt_' . uniqid(),
            'data' => [
                'firebase_uid' => 'test_abc123',
                'display_name' => 'Test Chatter',
                'email' => 'test@example.com',
            ],
        ]);

        $headers = $this->signPayload($payload);

        $response = $this->postJson('/api/webhook', json_decode($payload, true), $headers);

        $response->assertStatus(202);
        $this->assertDatabaseHas('webhook_events', [
            'event_type' => 'chatter.registered',
        ]);
    }

    public function test_missing_signature_returns_401(): void
    {
        $response = $this->postJson('/api/webhook', [
            'event_type' => 'chatter.registered',
            'data' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $payload = json_encode([
            'event_type' => 'chatter.registered',
            'data' => [],
        ]);

        $response = $this->postJson('/api/webhook', json_decode($payload, true), [
            'X-Webhook-Signature' => 'invalid-signature',
            'X-Webhook-Timestamp' => (string) time(),
        ]);

        $response->assertStatus(401);
    }

    public function test_expired_timestamp_returns_401(): void
    {
        $payload = json_encode([
            'event_type' => 'chatter.registered',
            'data' => [],
        ]);

        $oldTimestamp = time() - 600; // 10 minutes ago (>5 min threshold)
        $headers = $this->signPayload($payload, $oldTimestamp);

        $response = $this->postJson('/api/webhook', json_decode($payload, true), $headers);

        $response->assertStatus(401);
    }

    public function test_idempotency_prevents_duplicate_processing(): void
    {
        $eventId = 'evt_' . uniqid();
        $payload = json_encode([
            'event_type' => 'chatter.sale_completed',
            'event_id' => $eventId,
            'data' => ['firebase_uid' => 'test_xyz'],
        ]);

        $headers = $this->signPayload($payload);

        // First request
        $this->postJson('/api/webhook', json_decode($payload, true), $headers);

        // Second request with same event_id
        $headers2 = $this->signPayload($payload);
        $this->postJson('/api/webhook', json_decode($payload, true), $headers2);

        $this->assertEquals(
            1,
            WebhookEvent::where('external_event_id', $eventId)->count()
        );
    }

    public function test_health_endpoint_returns_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'database', 'redis']);
    }
}
