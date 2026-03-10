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
        Schema::create('message_link_clicks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_log_id');
            $table->uuid('chatter_id');
            $table->text('url');
            $table->timestampTz('clicked_at')->useCurrent();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
        });

        DB::statement('CREATE INDEX idx_mlc_message ON message_link_clicks (message_log_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('message_link_clicks');
    }
};
