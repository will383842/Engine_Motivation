<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE activity_log (
                id UUID NOT NULL DEFAULT uuid_generate_v4(),
                admin_id UUID,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50),
                entity_id UUID,
                old_values JSONB,
                new_values JSONB,
                ip_address INET,
                user_agent TEXT,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        ");

        for ($i = 0; $i < 4; $i++) {
            $start = date('Y-m-01', strtotime("+{$i} months"));
            $end = date('Y-m-01', strtotime('+' . ($i + 1) . ' months'));
            $suffix = date('Y_m', strtotime("+{$i} months"));
            DB::statement("CREATE TABLE IF NOT EXISTS activity_log_{$suffix} PARTITION OF activity_log FOR VALUES FROM ('{$start}') TO ('{$end}')");
        }

        DB::statement('CREATE INDEX idx_al_admin ON activity_log (admin_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_al_entity ON activity_log (entity_type, entity_id, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS activity_log CASCADE');
    }
};
