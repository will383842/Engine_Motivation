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
        Schema::create('chatter_health_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chatter_id');
            $table->foreign('chatter_id')->references('id')->on('chatters')->cascadeOnDelete();
            $table->decimal('health_score', 5, 2);
            $table->decimal('churn_risk', 5, 2)->default(0);
            $table->bigInteger('predicted_ltv_cents')->nullable();
            $table->jsonb('factors')->default('{}');
            $table->date('snapshot_date');
            $table->unique(['chatter_id', 'snapshot_date']);
        });

        DB::statement('CREATE INDEX idx_chs_risk ON chatter_health_scores (churn_risk DESC, snapshot_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('chatter_health_scores');
    }
};
