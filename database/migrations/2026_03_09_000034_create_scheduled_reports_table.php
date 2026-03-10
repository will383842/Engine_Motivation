<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('report_type', 50);
            $table->string('schedule_cron', 100);
            $table->jsonb('recipients')->default('[]');
            $table->jsonb('filters')->default('{}');
            $table->string('format', 10)->default('pdf');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_sent_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
