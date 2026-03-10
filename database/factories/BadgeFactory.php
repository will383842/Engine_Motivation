<?php
declare(strict_types=1);
namespace Database\Factories;
use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;
class BadgeFactory extends Factory {
    protected $model = Badge::class;
    public function definition(): array {
        return [
            "slug" => $this->faker->unique()->slug(2),
            "names" => ["en" => $this->faker->words(2, true)],
            "descriptions" => ["en" => $this->faker->sentence()],
            "icon_url" => "badge-default.svg",
            "category" => $this->faker->randomElement(["sales","social","streak","milestone","special"]),
            "xp_reward" => $this->faker->numberBetween(10, 200),
            "criteria" => ["type" => "manual"],
            "is_active" => true,
        ];
    }
}