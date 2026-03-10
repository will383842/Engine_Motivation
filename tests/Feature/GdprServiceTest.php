<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chatter;
use App\Models\ChatterSequence;
use App\Models\ConsentRecord;
use App\Models\Sequence;
use App\Models\SuppressionList;
use App\Services\GdprService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprServiceTest extends TestCase
{
    use RefreshDatabase;

    private GdprService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GdprService();
    }

    public function test_anonymize_removes_pii(): void
    {
        $chatter = Chatter::factory()->create([
            'email' => 'user@example.com',
            'phone' => '+33612345678',
            'whatsapp_phone' => '+33612345678',
            'display_name' => 'John Doe',
            'telegram_id' => '123456',
        ]);

        $this->service->anonymize($chatter);

        $chatter->refresh();
        $this->assertNull($chatter->getRawOriginal('email'));
        $this->assertNull($chatter->getRawOriginal('phone'));
        $this->assertNull($chatter->getRawOriginal('whatsapp_phone'));
        $this->assertNull($chatter->telegram_id);
        $this->assertEquals('Anonymized User', $chatter->display_name);
        $this->assertFalse($chatter->is_active);
        $this->assertEquals('sunset', $chatter->lifecycle_state);
    }

    public function test_anonymize_creates_suppression_entry(): void
    {
        $chatter = Chatter::factory()->create();

        $this->service->anonymize($chatter);

        $this->assertDatabaseHas('suppression_lists', [
            'chatter_id' => $chatter->id,
            'channel' => 'all',
            'reason' => 'gdpr_erasure',
            'source' => 'gdpr',
        ]);
    }

    public function test_anonymize_cancels_active_sequences(): void
    {
        $chatter = Chatter::factory()->create();
        $sequence = Sequence::factory()->create(['status' => 'active']);

        ChatterSequence::create([
            'chatter_id' => $chatter->id,
            'sequence_id' => $sequence->id,
            'status' => 'active',
            'current_step_order' => 0,
        ]);

        ChatterSequence::create([
            'chatter_id' => $chatter->id,
            'sequence_id' => $sequence->id,
            'status' => 'completed',
            'current_step_order' => 3,
        ]);

        $this->service->anonymize($chatter);

        $activeSeqs = ChatterSequence::where('chatter_id', $chatter->id)
            ->where('status', 'active')
            ->count();
        $exitedSeqs = ChatterSequence::where('chatter_id', $chatter->id)
            ->where('status', 'exited')
            ->where('exit_reason', 'gdpr_erasure')
            ->count();

        $this->assertEquals(0, $activeSeqs);
        $this->assertEquals(1, $exitedSeqs);
    }

    public function test_export_data_returns_structured_data(): void
    {
        $chatter = Chatter::factory()->create([
            'display_name' => 'Jane Doe',
            'level' => 5,
            'total_xp' => 1234,
        ]);

        $data = $this->service->exportData($chatter);

        $this->assertArrayHasKey('personal_data', $data);
        $this->assertArrayHasKey('gamification', $data);
        $this->assertArrayHasKey('missions', $data);
        $this->assertArrayHasKey('message_history', $data);
        $this->assertArrayHasKey('consent_records', $data);

        $this->assertEquals('Jane Doe', $data['personal_data']['display_name']);
        $this->assertEquals(5, $data['gamification']['level']);
        $this->assertEquals(1234, $data['gamification']['xp']);
    }

    public function test_record_consent(): void
    {
        $chatter = Chatter::factory()->create();

        $this->service->recordConsent(
            $chatter,
            'messaging',
            'telegram',
            'I agree to receive messages',
            '1.0',
            '192.168.1.1'
        );

        $this->assertDatabaseHas('consent_records', [
            'chatter_id' => $chatter->id,
            'consent_type' => 'messaging',
            'granted' => true,
            'ip_address' => '192.168.1.1',
            'version' => '1.0',
        ]);
    }

    public function test_revoke_consent(): void
    {
        $chatter = Chatter::factory()->create();

        ConsentRecord::create([
            'chatter_id' => $chatter->id,
            'consent_type' => 'messaging',
            'granted' => true,
            'consent_text' => 'I agree',
            'version' => '1.0',
        ]);

        $this->service->revokeConsent($chatter, 'messaging');

        $record = ConsentRecord::where('chatter_id', $chatter->id)->first();
        $this->assertNotNull($record->revoked_at);
    }
}
