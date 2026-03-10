<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "btree_gin"');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS "btree_gin"');
        DB::statement('DROP EXTENSION IF EXISTS "pg_trgm"');
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
