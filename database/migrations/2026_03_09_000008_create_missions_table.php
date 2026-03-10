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
        DB::statement("CREATE TYPE mission_type AS ENUM ('one_time','daily','weekly','monthly','recurring','streak_based','event_triggered')");
        DB::statement("CREATE TYPE mission_status AS ENUM ('active','paused','archived')");
        DB::statement("CREATE TYPE chatter_mission_st AS ENUM ('assigned','in_progress','completed','failed','expired')");

        Schema::create('missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();
            $table->jsonb('names')->default('{}');
            $table->jsonb('descriptions')->default('{}');
            $table->string('type', 30)->default('one_time');
            $table->string('status', 20)->default('active');
            $table->jsonb('criteria')->default('{}');
            $table->integer('target_count')->default(1);
            $table->integer('xp_reward')->default(0);
            $table->integer('bonus_cents')->default(0);
            $table->uuid('badge_id')->nullable();
            $table->foreign('badge_id')->references('id')->on('badges')->nullOnDelete();
            $table->timestampTz('available_from')->nullable();
            $table->timestampTz('available_until')->nullable();
            $table->integer('cooldown_hours')->nullable();
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
        DB::statement('DROP TYPE IF EXISTS chatter_mission_st');
        DB::statement('DROP TYPE IF EXISTS mission_status');
        DB::statement('DROP TYPE IF EXISTS mission_type');
    }
};
