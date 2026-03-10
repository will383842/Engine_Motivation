<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number', 30)->unique();
            $table->string('twilio_sid', 64);
            $table->string('display_name', 255)->default('SOS Expat');
            $table->string('country_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            // Warm-up
            $table->date('warmup_start_date')->useCurrent();
            $table->integer('warmup_week')->default(1);
            $table->integer('current_daily_limit')->default(30);
            $table->integer('current_tier')->default(0);
            // Health
            $table->string('quality_rating', 10)->default('green');
            $table->string('circuit_breaker_state', 20)->default('open');
            $table->timestampTz('circuit_breaker_until')->nullable();
            $table->text('circuit_breaker_reason')->nullable();
            $table->decimal('health_score', 5, 2)->default(100);
            // Stats
            $table->bigInteger('total_sent')->default(0);
            $table->bigInteger('total_delivered')->default(0);
            $table->bigInteger('total_blocked')->default(0);
            $table->bigInteger('total_cost_cents')->default(0);
            // Budget
            $table->integer('daily_budget_cap_cents')->nullable();
            // Meta
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
