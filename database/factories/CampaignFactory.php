<?php
declare(strict_types=1);
namespace Database\Factories;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
class CampaignFactory extends Factory {
    protected $model = Campaign::class;
    public function definition(): array {
        return [
            "name" => $this->faker->sentence(3),
            "status" => "draft",
            "channel" => "telegram",
            "scheduled_at" => $this->faker->optional()->dateTimeBetween("now", "+7 days"),
        ];
    }
}