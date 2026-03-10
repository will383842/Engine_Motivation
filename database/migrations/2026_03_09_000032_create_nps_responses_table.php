<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nps_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->integer('score');
            $table->text('comment')->nullable();
            $table->string('channel', 20);
            $table->string('survey_version', 20)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nps_responses');
    }
};
