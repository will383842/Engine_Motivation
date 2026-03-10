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
        DB::statement("CREATE TYPE suppression_reason AS ENUM ('opt_out','blocked','bounced','spam_reported','gdpr_erasure','admin_manual','sunset_policy','invalid_number','duplicate')");

        Schema::create('suppression_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('reason', 30);
            $table->string('source', 50);
            $table->text('notes')->nullable();
            $table->timestampTz('suppressed_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('lifted_at')->nullable();
            $table->uuid('lifted_by')->nullable();
            $table->foreign('lifted_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE UNIQUE INDEX uq_suppression_active ON suppression_list (chatter_id, channel) WHERE lifted_at IS NULL');
        DB::statement('CREATE INDEX idx_sl_active ON suppression_list (channel) WHERE lifted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_list');
        DB::statement('DROP TYPE IF EXISTS suppression_reason');
    }
};
