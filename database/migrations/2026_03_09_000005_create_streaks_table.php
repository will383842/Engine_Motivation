<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id')->unique();
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->integer('current_count')->default(0);
            $table->integer('longest_count')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->date('streak_frozen_until')->nullable();
            $table->integer('freeze_count_used')->default(0);
            $table->integer('freeze_count_max')->default(1);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('broken_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaks');
    }
};
