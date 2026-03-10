<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_health_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('quality_rating', 10);
            $table->integer('tier')->default(0);
            $table->integer('sent_24h')->default(0);
            $table->integer('delivered_24h')->default(0);
            $table->integer('blocked_24h')->default(0);
            $table->decimal('block_rate', 5, 2)->default(0);
            $table->string('circuit_breaker_state', 20)->default('open');
            $table->integer('warmup_week')->default(1);
            $table->integer('daily_limit');
            $table->integer('budget_spent_cents')->default(0);
            $table->timestampTz('checked_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_whl_date ON whatsapp_health_logs (checked_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_health_logs');
    }
};
