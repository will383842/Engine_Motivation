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
        Schema::create('chatter_lifecycle_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('from_state', 20);
            $table->string('to_state', 20);
            $table->string('reason', 255)->nullable();
            $table->string('triggered_by', 50)->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_clt_chatter ON chatter_lifecycle_transitions (chatter_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_lifecycle_transitions');
    }
};
