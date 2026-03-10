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
        Schema::create('revenue_attributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->bigInteger('commission_cents');
            $table->string('attributed_to', 50)->nullable();
            $table->uuid('attributed_message_id')->nullable();
            $table->integer('attribution_window_hours')->default(168);
            $table->string('firebase_event_id', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_ra_chatter ON revenue_attributions (chatter_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_ra_source ON revenue_attributions (attributed_to, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_attributions');
    }
};
