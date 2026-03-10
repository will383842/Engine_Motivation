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
        DB::statement("CREATE TYPE league_tier AS ENUM ('bronze','silver','gold','platinum','diamond','master','legend')");

        Schema::create('leagues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tier', 20);
            $table->string('week_key', 10);
            $table->integer('max_participants')->default(30);
            $table->integer('promotion_count')->default(5);
            $table->integer('relegation_count')->default(5);
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['tier', 'week_key']);
        });

        Schema::create('league_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('chatter_id');
            $table->foreign('league_id')->references('id')->on('leagues')->cascadeOnDelete();
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->bigInteger('weekly_xp')->default(0);
            $table->integer('rank')->nullable();
            $table->boolean('promoted')->default(false);
            $table->boolean('relegated')->default(false);
            $table->unique(['league_id', 'chatter_id']);
        });

        DB::statement('CREATE INDEX idx_lp_league_rank ON league_participants (league_id, weekly_xp DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('league_participants');
        Schema::dropIfExists('leagues');
        DB::statement('DROP TYPE IF EXISTS league_tier');
    }
};
