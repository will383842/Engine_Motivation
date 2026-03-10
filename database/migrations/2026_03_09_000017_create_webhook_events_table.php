<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE event_processing_st AS ENUM ('pending','processing','processed','failed','skipped')");

        DB::statement("
            CREATE TABLE webhook_events (
                id UUID NOT NULL DEFAULT uuid_generate_v4(),
                source VARCHAR(50) NOT NULL,
                external_event_id VARCHAR(255) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                payload JSONB NOT NULL DEFAULT '{}',
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                processed_at TIMESTAMPTZ,
                attempts INT NOT NULL DEFAULT 0,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id, created_at),
                UNIQUE (source, external_event_id, created_at)
            ) PARTITION BY RANGE (created_at)
        ");

        for ($i = 0; $i < 4; $i++) {
            $start = date('Y-m-01', strtotime("+{$i} months"));
            $end = date('Y-m-01', strtotime('+' . ($i + 1) . ' months'));
            $suffix = date('Y_m', strtotime("+{$i} months"));
            DB::statement("CREATE TABLE IF NOT EXISTS webhook_events_{$suffix} PARTITION OF webhook_events FOR VALUES FROM ('{$start}') TO ('{$end}')");
        }

        DB::statement("CREATE INDEX idx_we_status ON webhook_events (status, created_at) WHERE status = 'pending'");
        DB::statement('CREATE INDEX idx_we_event_type ON webhook_events (event_type, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS webhook_events CASCADE');
        DB::statement('DROP TYPE IF EXISTS event_processing_st');
    }
};
