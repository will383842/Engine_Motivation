<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE message_status AS ENUM ('pending','queued','sent','delivered','read','failed','bounced','opted_out')");

        // Partitioned table — must use raw SQL
        DB::statement("
            CREATE TABLE message_logs (
                id UUID NOT NULL DEFAULT uuid_generate_v4(),
                chatter_id UUID NOT NULL,
                channel VARCHAR(20) NOT NULL,
                direction VARCHAR(10) NOT NULL DEFAULT 'outbound',
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                source_type VARCHAR(30),
                source_id UUID,
                template_id UUID,
                body TEXT,
                external_msg_id VARCHAR(255),
                sender_id UUID,
                sender_type VARCHAR(20),
                interaction_type VARCHAR(20),
                sent_at TIMESTAMPTZ,
                delivered_at TIMESTAMPTZ,
                read_at TIMESTAMPTZ,
                failed_at TIMESTAMPTZ,
                error_code VARCHAR(50),
                cost_cents INT NOT NULL DEFAULT 0,
                metadata JSONB NOT NULL DEFAULT '{}',
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        ");

        // Create partitions for current and next 3 months
        $months = [];
        for ($i = 0; $i < 4; $i++) {
            $start = date('Y-m-01', strtotime("+{$i} months"));
            $end = date('Y-m-01', strtotime('+' . ($i + 1) . ' months'));
            $suffix = date('Y_m', strtotime("+{$i} months"));
            $months[] = "CREATE TABLE IF NOT EXISTS message_logs_{$suffix} PARTITION OF message_logs FOR VALUES FROM ('{$start}') TO ('{$end}')";
        }
        foreach ($months as $sql) {
            DB::statement($sql);
        }

        DB::statement('CREATE INDEX idx_ml_chatter ON message_logs (chatter_id, created_at DESC)');
        DB::statement("CREATE INDEX idx_ml_status ON message_logs (status, created_at) WHERE status IN ('pending','queued')");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS message_logs CASCADE');
        DB::statement('DROP TYPE IF EXISTS message_status');
    }
};
