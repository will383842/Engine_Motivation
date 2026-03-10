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
        DB::statement("CREATE TYPE admin_role AS ENUM ('super_admin','admin','manager','analyst','viewer')");

        Schema::create('admin_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 320)->unique();
            $table->string('name', 255);
            $table->string('password', 255);
            $table->string('role', 20)->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
        DB::statement('DROP TYPE IF EXISTS admin_role');
    }
};
