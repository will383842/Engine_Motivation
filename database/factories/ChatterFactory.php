<?php
declare(strict_types=1);
namespace Database\Factories;
use App\Models\Chatter;
use Illuminate\Database\Eloquent\Factories\Factory;
class ChatterFactory extends Factory {
    protected $model = Chatter::class;
    public function definition(): array {
        return [
            "firebase_uid" => "test_" . $this->faker->uuid(),
            "display_name" => $this->faker->name(),
            "email" => $this->faker->unique()->safeEmail(),
            "phone" => $this->faker->e164PhoneNumber(),
            "language" => $this->faker->randomElement(["fr","en","es","de","pt"]),
            "country" => $this->faker->countryCode(),
            "timezone" => $this->faker->timezone(),
            "level" => $this->faker->numberBetween(1, 50),
            "total_xp" => $this->faker->numberBetween(0, 50000),
            "preferred_channel" => $this->faker->randomElement(["telegram","whatsapp"]),
            "lifecycle_state" => "active",
            "current_streak" => $this->faker->numberBetween(0, 30),
            "is_active" => true,
        ];
    }
}