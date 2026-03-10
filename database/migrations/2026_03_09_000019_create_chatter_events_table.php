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
        Schema::create('chatter_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('event_type', 100);
            $table->jsonb('event_data')->default('{}');
            $table->string('firebase_event_id', 255)->nullable();
            $table->timestampTz('occurred_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_ce_chatter ON chatter_events (chatter_id, occurred_at DESC)');
        DB::statement('CREATE INDEX idx_ce_type ON chatter_events (event_type, occurred_at DESC)');
        DB::statement('CREATE UNIQUE INDEX uq_ce_firebase ON chatter_events (firebase_event_id) WHERE firebase_event_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_events');
    }
};
