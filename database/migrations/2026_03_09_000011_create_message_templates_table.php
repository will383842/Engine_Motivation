<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 150)->unique();
            $table->string('name', 255);
            $table->string('category', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('message_template_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->foreign('template_id')->references('id')->on('message_templates')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('language', 5)->default('en');
            $table->text('body');
            $table->text('media_url')->nullable();
            $table->jsonb('buttons')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['template_id', 'channel', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_template_variants');
        Schema::dropIfExists('message_templates');
    }
};
