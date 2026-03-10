<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('exit_conditions');
            $table->uuid('parent_sequence_id')->nullable()->after('version');
            $table->foreign('parent_sequence_id')->references('id')->on('sequences')->nullOnDelete();
            $table->jsonb('snapshot_before_edit')->nullable()->after('parent_sequence_id');
        });
    }

    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropForeign(['parent_sequence_id']);
            $table->dropColumn(['version', 'parent_sequence_id', 'snapshot_before_edit']);
        });
    }
};
