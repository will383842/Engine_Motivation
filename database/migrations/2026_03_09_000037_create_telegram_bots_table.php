<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('bot_username', 100)->unique();
            $table->text('bot_token_encrypted');
            $table->string('role', 20)->default('primary');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_restricted')->default(false);
            // Stats
            $table->integer('assigned_chatters_count')->default(0);
            $table->bigInteger('total_sent')->default(0);
            $table->bigInteger('total_failed')->default(0);
            // Health
            $table->decimal('health_score', 5, 2)->default(100);
            $table->timestampTz('last_health_check_at')->nullable();
            $table->integer('consecutive_failures')->default(0);
            // Meta
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bots');
    }
};
