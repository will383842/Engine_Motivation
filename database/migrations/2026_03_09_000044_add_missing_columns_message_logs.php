<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE message_logs ADD COLUMN clicked_at TIMESTAMPTZ');
        DB::statement('ALTER TABLE message_logs ADD COLUMN replied_at TIMESTAMPTZ');
        DB::statement('ALTER TABLE message_logs ADD COLUMN click_count INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE message_logs ADD COLUMN reply_content TEXT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE message_logs DROP COLUMN IF EXISTS clicked_at');
        DB::statement('ALTER TABLE message_logs DROP COLUMN IF EXISTS replied_at');
        DB::statement('ALTER TABLE message_logs DROP COLUMN IF EXISTS click_count');
        DB::statement('ALTER TABLE message_logs DROP COLUMN IF EXISTS reply_content');
    }
};
