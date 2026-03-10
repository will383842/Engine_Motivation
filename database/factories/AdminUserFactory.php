<?php
declare(strict_types=1);
namespace Database\Factories;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
class AdminUserFactory extends Factory {
    protected $model = AdminUser::class;
    public function definition(): array {
        return [
            "name" => $this->faker->name(),
            "email" => $this->faker->unique()->safeEmail(),
            "password" => Hash::make("password"),
            "role" => "admin",
            "is_active" => true,
            "two_factor_enabled" => false,
        ];
    }
}