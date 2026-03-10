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
        DB::statement("CREATE TYPE segment_operator AS ENUM ('and','or')");
        DB::statement("CREATE TYPE rule_operator AS ENUM ('eq','neq','gt','gte','lt','lte','in','not_in','contains','between','is_null','is_not_null','regex')");

        Schema::create('segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('operator', 10)->default('and');
            $table->boolean('is_dynamic')->default(true);
            $table->integer('cached_count')->nullable();
            $table->timestampTz('cached_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('segment_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('segment_id');
            $table->foreign('segment_id')->references('id')->on('segments')->cascadeOnDelete();
            $table->string('field', 255);
            $table->string('operator', 30);
            $table->jsonb('value');
            $table->integer('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_rules');
        Schema::dropIfExists('segments');
        DB::statement('DROP TYPE IF EXISTS rule_operator');
        DB::statement('DROP TYPE IF EXISTS segment_operator');
    }
};
