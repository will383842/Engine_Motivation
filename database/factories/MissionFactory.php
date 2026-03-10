<?php
declare(strict_types=1);
namespace Database\Factories;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;
class MissionFactory extends Factory {
    protected $model = Mission::class;
    public function definition(): array {
        return [
            "slug" => $this->faker->unique()->slug(2),
            "names" => ["en" => $this->faker->sentence(3), "fr" => $this->faker->sentence(3)],
            "descriptions" => ["en" => $this->faker->sentence()],
            "type" => $this->faker->randomElement(["daily","weekly","monthly","one_time"]),
            "status" => "active",
            "target_count" => $this->faker->numberBetween(1, 100),
            "xp_reward" => $this->faker->numberBetween(10, 500),
            "is_active" => true,
        ];
    }
}