<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chatter;
use App\Models\ChatterSequence;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Services\MotivationDispatcher;
use App\Services\SegmentResolver;
use App\Services\SequenceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class SequenceEngineTest extends TestCase
{
    use RefreshDatabase;

    private SequenceEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();

        $dispatcher = Mockery::mock(MotivationDispatcher::class);
        $dispatcher->shouldReceive('send')->andReturn(true);
        $dispatcher->shouldReceive('resolveChannel')->andReturn('telegram');

        $segmentResolver = Mockery::mock(SegmentResolver::class);

        $this->engine = new SequenceEngine($dispatcher, $segmentResolver);
    }

    public function test_enroll_active_sequence(): void
    {
        $chatter = Chatter::factory()->create();
        $sequence = Sequence::factory()->create(['status' => 'active']);

        SequenceStep::create([
            'sequence_id' => $sequence->id,
            'type' => 'message',
            'step_order' => 1,
            'delay_seconds' => 0,
        ]);

        $enrollment = $this->engine->enroll($chatter, $sequence);

        $this->assertNotNull($enrollment);
        $this->assertEquals('active', $enrollment->status);
        $this->assertEquals($chatter->id, $enrollment->chatter_id);
    }

    public function test_enroll_inactive_sequence_returns_null(): void
    {
        $chatter = Chatter::factory()->create();
        $sequence = Sequence::factory()->create(['status' => 'draft']);

        $enrollment = $this->engine->enroll($chatter, $sequence);

        $this->assertNull($enrollment);
    }

    public function test_enroll_respects_max_concurrent(): void
    {
        $chatter = Chatter::factory()->create();

        // Create 3 active enrollments (max_concurrent default is 3)
        for ($i = 0; $i < 3; $i++) {
            $seq = Sequence::factory()->create(['status' => 'active']);
            ChatterSequence::create([
                'chatter_id' => $chatter->id,
                'sequence_id' => $seq->id,
                'status' => 'active',
                'current_step_order' => 0,
            ]);
        }

        $newSequence = Sequence::factory()->create(['status' => 'active', 'max_concurrent' => 3]);
        $enrollment = $this->engine->enroll($chatter, $newSequence);

        $this->assertNull($enrollment);
    }

    public function test_advance_completes_sequence_at_last_step(): void
    {
        $chatter = Chatter::factory()->create();
        $sequence = Sequence::factory()->create(['status' => 'active']);

        $step = SequenceStep::create([
            'sequence_id' => $sequence->id,
            'type' => 'message',
            'step_order' => 1,
            'delay_seconds' => 0,
        ]);

        $enrollment = ChatterSequence::create([
            'chatter_id' => $chatter->id,
            'sequence_id' => $sequence->id,
            'current_step_id' => $step->id,
            'current_step_order' => 1,
            'status' => 'active',
            'next_step_at' => now()->subMinute(),
        ]);

        $this->engine->processStep($enrollment);

        $enrollment->refresh();
        $this->assertEquals('completed', $enrollment->status);
    }

    public function test_exit_condition_stops_sequence(): void
    {
        $chatter = Chatter::factory()->create(['is_active' => false]);
        $sequence = Sequence::factory()->create([
            'status' => 'active',
            'exit_conditions' => [
                ['type' => 'opt_out'],
            ],
        ]);

        $step = SequenceStep::create([
            'sequence_id' => $sequence->id,
            'type' => 'message',
            'step_order' => 1,
            'delay_seconds' => 0,
        ]);

        $enrollment = ChatterSequence::create([
            'chatter_id' => $chatter->id,
            'sequence_id' => $sequence->id,
            'current_step_id' => $step->id,
            'current_step_order' => 1,
            'status' => 'active',
            'next_step_at' => now()->subMinute(),
        ]);

        $this->engine->processStep($enrollment);

        $enrollment->refresh();
        $this->assertEquals('exited', $enrollment->status);
        $this->assertEquals('exit_condition_met', $enrollment->exit_reason);
    }

    public function test_delay_step_schedules_next(): void
    {
        $chatter = Chatter::factory()->create();
        $sequence = Sequence::factory()->create(['status' => 'active']);

        $step1 = SequenceStep::create([
            'sequence_id' => $sequence->id,
            'type' => 'delay',
            'step_order' => 1,
            'delay_seconds' => 3600, // 1 hour
        ]);

        $step2 = SequenceStep::create([
            'sequence_id' => $sequence->id,
            'type' => 'message',
            'step_order' => 2,
            'delay_seconds' => 0,
        ]);

        $enrollment = ChatterSequence::create([
            'chatter_id' => $chatter->id,
            'sequence_id' => $sequence->id,
            'current_step_id' => $step1->id,
            'current_step_order' => 1,
            'status' => 'active',
            'next_step_at' => now()->subMinute(),
        ]);

        $this->engine->processStep($enrollment);

        $enrollment->refresh();
        $this->assertEquals('active', $enrollment->status);
        $this->assertEquals($step2->id, $enrollment->current_step_id);
        $this->assertTrue($enrollment->next_step_at->isFuture());
    }

    public function test_no_duplicate_enrollment_for_non_repeatable(): void
    {
        $chatter = Chatter::factory()->create();
        $sequence = Sequence::factory()->create(['status' => 'active', 'is_repeatable' => false]);

        SequenceStep::create([
            'sequence_id' => $sequence->id,
            'type' => 'message',
            'step_order' => 1,
        ]);

        $this->engine->enroll($chatter, $sequence);
        $second = $this->engine->enroll($chatter, $sequence);

        $this->assertNull($second);
    }
}
