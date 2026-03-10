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
        Schema::create('admin_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('severity', 20);
            $table->string('category', 50);
            $table->text('message');
            $table->jsonb('channels_notified')->default('[]');
            $table->uuid('acknowledged_by')->nullable();
            $table->foreign('acknowledged_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_alerts_severity ON admin_alerts (severity, created_at DESC)');
        DB::statement('CREATE INDEX idx_alerts_unacked ON admin_alerts (created_at DESC) WHERE acknowledged_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_alerts');
    }
};
