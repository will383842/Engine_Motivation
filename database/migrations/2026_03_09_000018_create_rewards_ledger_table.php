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
        DB::statement("CREATE TYPE reward_type AS ENUM ('badge','bonus_cents','xp','streak_freeze','custom')");
        DB::statement("CREATE TYPE ledger_source AS ENUM ('mission','badge','streak','campaign','manual','ab_test','referral')");

        Schema::create('rewards_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('reward_type', 20);
            $table->integer('amount');
            $table->string('source', 20);
            $table->uuid('source_id')->nullable();
            $table->text('description')->nullable();
            $table->uuid('granted_by')->nullable();
            $table->foreign('granted_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_rl_chatter ON rewards_ledger (chatter_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards_ledger');
        DB::statement('DROP TYPE IF EXISTS ledger_source');
        DB::statement('DROP TYPE IF EXISTS reward_type');
    }
};
