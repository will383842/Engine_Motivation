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
        Schema::create('fraud_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('flag_type', 50);
            $table->string('severity', 20)->default('low');
            $table->jsonb('evidence')->default('{}');
            $table->boolean('resolved')->default(false);
            $table->uuid('resolved_by')->nullable();
            $table->foreign('resolved_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_ff_unresolved ON fraud_flags (severity DESC, created_at DESC) WHERE resolved = FALSE');

        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('rule_type', 50);
            $table->jsonb('conditions');
            $table->string('action', 50);
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_rules');
        Schema::dropIfExists('fraud_flags');
    }
};
