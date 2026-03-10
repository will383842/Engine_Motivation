<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->boolean('is_opted_in')->default(true);
            $table->timestampTz('opted_out_at')->nullable();
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->unique(['chatter_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
