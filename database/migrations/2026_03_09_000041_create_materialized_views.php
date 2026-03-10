<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW mv_leaderboard_weekly AS
            SELECT c.id AS chatter_id, c.display_name, c.country,
                COALESCE(SUM(rl.amount) FILTER (WHERE rl.reward_type='xp'), 0) AS weekly_xp,
                RANK() OVER (ORDER BY COALESCE(SUM(rl.amount) FILTER (WHERE rl.reward_type='xp'),0) DESC) AS global_rank
            FROM chatters c
            LEFT JOIN rewards_ledger rl ON rl.chatter_id = c.id
                AND rl.created_at >= date_trunc('week', now())
            WHERE c.is_active = TRUE
            GROUP BY c.id
        ");

        DB::statement('CREATE UNIQUE INDEX idx_mv_lb_weekly_chatter ON mv_leaderboard_weekly (chatter_id)');

        // Partition creation function
        DB::statement("
            CREATE OR REPLACE FUNCTION create_next_month_partitions() RETURNS void AS \$\$
            DECLARE
                next_start DATE := date_trunc('month', now() + INTERVAL '2 months')::date;
                next_end DATE := (next_start + INTERVAL '1 month')::date;
                suffix TEXT := to_char(next_start, 'YYYY_MM');
            BEGIN
                EXECUTE format('CREATE TABLE IF NOT EXISTS message_logs_%s PARTITION OF message_logs FOR VALUES FROM (%L) TO (%L)', suffix, next_start, next_end);
                EXECUTE format('CREATE TABLE IF NOT EXISTS webhook_events_%s PARTITION OF webhook_events FOR VALUES FROM (%L) TO (%L)', suffix, next_start, next_end);
                EXECUTE format('CREATE TABLE IF NOT EXISTS activity_log_%s PARTITION OF activity_log FOR VALUES FROM (%L) TO (%L)', suffix, next_start, next_end);
            END;
            \$\$ LANGUAGE plpgsql
        ");
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS create_next_month_partitions()');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_leaderboard_weekly');
    }
};
