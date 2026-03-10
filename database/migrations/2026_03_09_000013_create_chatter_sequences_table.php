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
        Schema::create('chatter_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->uuid('sequence_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->foreign('sequence_id')->references('id')->on('sequences')->cascadeOnDelete();
            $table->uuid('current_step_id')->nullable();
            $table->foreign('current_step_id')->references('id')->on('sequence_steps')->nullOnDelete();
            $table->integer('current_step_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestampTz('enrolled_at')->useCurrent();
            $table->timestampTz('next_step_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->string('exit_reason', 100)->nullable();
        });

        DB::statement("CREATE UNIQUE INDEX uq_chatter_seq_active ON chatter_sequences (chatter_id, sequence_id) WHERE status = 'active'");
        DB::statement("CREATE INDEX idx_cs_next ON chatter_sequences (next_step_at) WHERE status = 'active' AND next_step_at IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_sequences');
    }
};
