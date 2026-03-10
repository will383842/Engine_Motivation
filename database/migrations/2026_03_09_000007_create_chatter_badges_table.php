<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatter_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->uuid('badge_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->foreign('badge_id')->references('id')->on('badges')->cascadeOnDelete();
            $table->timestampTz('awarded_at')->useCurrent();
            $table->unique(['chatter_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_badges');
    }
};
