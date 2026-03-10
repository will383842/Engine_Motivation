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
        Schema::create('chatter_fatigue_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->decimal('fatigue_score', 5, 2)->default(0);
            $table->integer('messages_sent_7d')->default(0);
            $table->integer('messages_opened_7d')->default(0);
            $table->integer('messages_clicked_7d')->default(0);
            $table->timestampTz('last_interaction_at')->nullable();
            $table->integer('consecutive_ignored')->default(0);
            $table->decimal('frequency_multiplier', 3, 2)->default(1.0);
            $table->timestampTz('updated_at')->useCurrent();
            $table->unique(['chatter_id', 'channel']);
        });

        DB::statement('CREATE INDEX idx_cfs_fatigue ON chatter_fatigue_scores (fatigue_score DESC) WHERE fatigue_score > 50');
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_fatigue_scores');
    }
};
