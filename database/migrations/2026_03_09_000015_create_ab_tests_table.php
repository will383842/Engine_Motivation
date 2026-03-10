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
        DB::statement("CREATE TYPE ab_status AS ENUM ('draft','running','completed','cancelled')");

        Schema::create('ab_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('status', 20)->default('draft');
            $table->string('metric', 100)->default('read_rate');
            $table->decimal('confidence_level', 4, 2)->default(0.95);
            $table->jsonb('traffic_split')->default('[]');
            $table->uuid('winner_variant_id')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('ab_test_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ab_test_id');
            $table->foreign('ab_test_id')->references('id')->on('ab_tests')->cascadeOnDelete();
            $table->string('name', 100);
            $table->uuid('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('message_templates')->nullOnDelete();
            $table->integer('weight')->default(50);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->unique(['ab_test_id', 'name']);
        });

        Schema::create('chatter_ab_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->uuid('ab_test_id');
            $table->uuid('variant_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->foreign('ab_test_id')->references('id')->on('ab_tests')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('ab_test_variants')->cascadeOnDelete();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->boolean('converted')->default(false);
            $table->unique(['chatter_id', 'ab_test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_ab_assignments');
        Schema::dropIfExists('ab_test_variants');
        Schema::dropIfExists('ab_tests');
        DB::statement('DROP TYPE IF EXISTS ab_status');
    }
};
