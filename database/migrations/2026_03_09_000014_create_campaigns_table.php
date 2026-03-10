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
        DB::statement("CREATE TYPE campaign_status AS ENUM ('draft','scheduled','sending','sent','paused','cancelled','failed')");
        DB::statement("CREATE TYPE recipient_status AS ENUM ('pending','sent','delivered','failed','skipped','opted_out')");

        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('status', 20)->default('draft');
            $table->string('channel', 20)->default('telegram');
            $table->uuid('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('message_templates')->nullOnDelete();
            $table->uuid('segment_id')->nullable();
            $table->foreign('segment_id')->references('id')->on('segments')->nullOnDelete();
            $table->timestampTz('scheduled_at')->nullable();
            $table->boolean('timezone_aware')->default(false);
            $table->integer('send_rate_per_second')->default(30);
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->uuid('ab_test_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('chatter_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('channel', 20);
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('external_msg_id', 255)->nullable();
            $table->unique(['campaign_id', 'chatter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        DB::statement('DROP TYPE IF EXISTS recipient_status');
        DB::statement('DROP TYPE IF EXISTS campaign_status');
    }
};
