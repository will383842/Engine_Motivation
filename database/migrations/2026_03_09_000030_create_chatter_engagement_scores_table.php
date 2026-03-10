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
        Schema::create('chatter_engagement_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id')->unique();
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->decimal('engagement_score', 5, 2)->default(50);
            $table->decimal('activity_score', 5, 2)->default(0);
            $table->decimal('revenue_score', 5, 2)->default(0);
            $table->decimal('responsiveness_score', 5, 2)->default(0);
            $table->decimal('gamification_score', 5, 2)->default(0);
            $table->decimal('growth_score', 5, 2)->default(0);
            $table->string('trend', 10)->default('stable');
            $table->integer('percentile')->nullable();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_ces_score ON chatter_engagement_scores (engagement_score DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_engagement_scores');
    }
};
