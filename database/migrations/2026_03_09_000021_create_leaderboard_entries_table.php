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
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('period_type', 10);
            $table->string('period_key', 20);
            $table->string('metric', 50);
            $table->bigInteger('value')->default(0);
            $table->integer('rank')->nullable();
            $table->string('country', 3)->nullable();
            $table->unique(['chatter_id', 'period_type', 'period_key', 'metric']);
        });

        DB::statement('CREATE INDEX idx_lb_ranking ON leaderboard_entries (period_type, period_key, metric, value DESC, rank)');
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
