<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class AdminUserSeeder extends Seeder {
    public function run(): void {
        AdminUser::firstOrCreate(
            ["email" => "williamsjullin@gmail.com"],
            ["name" => "Williams Jullin", "password" => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'change-me-immediately')), "role" => "super_admin", "is_active" => true]
        );
    }
}
