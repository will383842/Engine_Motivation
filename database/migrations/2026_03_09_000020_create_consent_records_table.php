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
        Schema::create('consent_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->string('consent_type', 50);
            $table->boolean('granted');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('consent_text');
            $table->string('version', 20);
            $table->timestampTz('granted_at')->useCurrent();
            $table->timestampTz('revoked_at')->nullable();
            $table->unique(['chatter_id', 'consent_type', 'version']);
        });

        DB::statement('CREATE INDEX idx_cr_chatter ON consent_records (chatter_id, consent_type)');
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
