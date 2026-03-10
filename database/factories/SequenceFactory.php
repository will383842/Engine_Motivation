<?php
declare(strict_types=1);
namespace Database\Factories;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;
class SequenceFactory extends Factory {
    protected $model = Sequence::class;
    public function definition(): array {
        return [
            "name" => $this->faker->sentence(3),
            "status" => "active",
            "trigger_event" => $this->faker->randomElement(["chatter.registered","chatter.sale_completed"]),
            "priority" => $this->faker->numberBetween(1, 100),
            "is_repeatable" => false,
            "max_concurrent" => 3,
        ];
    }
}