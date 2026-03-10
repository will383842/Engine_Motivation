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
        DB::statement("CREATE TYPE channel_type AS ENUM ('telegram','whatsapp','email','push','sms','dashboard')");
        DB::statement("CREATE TYPE chatter_lifecycle AS ENUM ('registered','onboarding','active','declining','dormant','churned','sunset')");

        Schema::create('chatters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firebase_uid', 128)->unique();
            $table->text('email')->nullable(); // encrypted
            $table->text('phone')->nullable(); // encrypted
            $table->string('email_hash', 64)->nullable()->index();
            $table->string('display_name', 255);
            $table->string('affiliate_code_client', 30)->unique()->nullable();
            $table->string('affiliate_code_recruitment', 30)->unique()->nullable();
            $table->string('language', 5)->default('en');
            $table->string('country', 3)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->bigInteger('telegram_id')->unique()->nullable();
            $table->text('whatsapp_phone')->nullable(); // encrypted
            $table->boolean('whatsapp_opted_in')->default(false);
            $table->string('preferred_channel', 20)->default('telegram');
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->bigInteger('total_xp')->default(0);
            $table->integer('level')->default(1);
            $table->integer('badges_count')->default(0);
            $table->bigInteger('balance_cents')->default(0);
            $table->bigInteger('lifetime_earnings_cents')->default(0);
            $table->string('lifecycle_state', 20)->default('registered');
            $table->timestampTz('lifecycle_changed_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_active_at')->nullable();
            $table->timestampTz('last_synced_at')->nullable();
            $table->jsonb('extra')->default('{}');
            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_chatters_active ON chatters (is_active, last_active_at DESC) WHERE is_active = TRUE');
        DB::statement('CREATE INDEX idx_chatters_xp ON chatters (total_xp DESC) WHERE is_active = TRUE');
        DB::statement('CREATE INDEX idx_chatters_extra ON chatters USING GIN (extra)');
    }

    public function down(): void
    {
        Schema::dropIfExists('chatters');
        DB::statement('DROP TYPE IF EXISTS chatter_lifecycle');
        DB::statement('DROP TYPE IF EXISTS channel_type');
    }
};
