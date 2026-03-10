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
        Schema::create('sender_health_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sender_type', 20);
            $table->uuid('sender_id');
            $table->integer('sent_24h')->default(0);
            $table->integer('delivered_24h')->default(0);
            $table->integer('blocked_24h')->default(0);
            $table->integer('failed_24h')->default(0);
            $table->decimal('block_rate', 5, 2)->default(0);
            $table->string('quality_rating', 10)->nullable();
            $table->string('circuit_breaker_state', 20)->nullable();
            $table->decimal('health_score', 5, 2)->default(100);
            $table->timestampTz('checked_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_shl_sender ON sender_health_logs (sender_type, sender_id, checked_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sender_health_logs');
    }
};
