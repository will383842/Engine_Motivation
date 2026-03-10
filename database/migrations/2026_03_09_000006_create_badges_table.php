<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();
            $table->jsonb('names')->default('{}');
            $table->jsonb('descriptions')->default('{}');
            $table->text('icon_url')->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('xp_reward')->default(0);
            $table->jsonb('criteria')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
