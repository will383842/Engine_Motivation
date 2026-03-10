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
        Schema::create('chatter_missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->uuid('mission_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->foreign('mission_id')->references('id')->on('missions')->cascadeOnDelete();
            $table->string('status', 20)->default('assigned');
            $table->integer('progress_count')->default(0);
            $table->integer('target_count');
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->boolean('reward_granted')->default(false);
            $table->timestampsTz();
        });

        DB::statement("CREATE UNIQUE INDEX uq_chatter_mission_active ON chatter_missions (chatter_id, mission_id) WHERE status IN ('assigned','in_progress')");
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_missions');
    }
};
