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
        DB::statement("CREATE TYPE sequence_status AS ENUM ('draft','active','paused','archived')");
        DB::statement("CREATE TYPE step_type AS ENUM ('message','delay','condition','ab_split','webhook','action')");

        Schema::create('sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('trigger_event', 100)->nullable();
            $table->uuid('segment_id')->nullable();
            $table->foreign('segment_id')->references('id')->on('segments')->nullOnDelete();
            $table->integer('priority')->default(50);
            $table->integer('max_concurrent')->default(3);
            $table->boolean('is_repeatable')->default(false);
            $table->jsonb('exit_conditions')->default('[]');
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sequence_id');
            $table->foreign('sequence_id')->references('id')->on('sequences')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('type', 20)->default('message');
            $table->uuid('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('message_templates')->nullOnDelete();
            $table->string('channel', 20)->nullable();
            $table->integer('delay_seconds')->nullable();
            $table->jsonb('condition_rules')->nullable();
            $table->uuid('ab_test_id')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->unique(['sequence_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('sequences');
        DB::statement('DROP TYPE IF EXISTS step_type');
        DB::statement('DROP TYPE IF EXISTS sequence_status');
    }
};
