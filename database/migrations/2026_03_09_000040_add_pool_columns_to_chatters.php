<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatters', function (Blueprint $table) {
            $table->uuid('last_whatsapp_number_id')->nullable();
            $table->foreign('last_whatsapp_number_id')->references('id')->on('whatsapp_numbers')->nullOnDelete();
            $table->uuid('assigned_telegram_bot_id')->nullable();
            $table->foreign('assigned_telegram_bot_id')->references('id')->on('telegram_bots')->nullOnDelete();
            $table->string('league_tier', 20)->default('bronze');
        });
    }

    public function down(): void
    {
        Schema::table('chatters', function (Blueprint $table) {
            $table->dropForeign(['last_whatsapp_number_id']);
            $table->dropForeign(['assigned_telegram_bot_id']);
            $table->dropColumn(['last_whatsapp_number_id', 'assigned_telegram_bot_id', 'league_tier']);
        });
    }
};
