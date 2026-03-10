<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatter_send_time_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id')->unique();
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->integer('best_hour_local')->nullable();
            $table->integer('best_day_of_week')->nullable();
            $table->jsonb('interaction_heatmap')->default('{}');
            $table->integer('sample_size')->default(0);
            $table->decimal('confidence', 3, 2)->default(0);
            $table->timestampTz('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_send_time_profiles');
    }
};
