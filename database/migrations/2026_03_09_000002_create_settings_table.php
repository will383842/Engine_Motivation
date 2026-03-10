<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->jsonb('value')->default('{}');
            $table->text('description')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
