<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\Sequence;
use Illuminate\Database\Seeder;
class SequenceSeeder extends Seeder {
    public function run(): void {
        $sequences = [
            ["name" => "Onboarding", "trigger_event" => "chatter.registered", "priority" => 90, "status" => "active"],
            ["name" => "First Sale Celebration", "trigger_event" => "chatter.first_sale", "priority" => 95, "status" => "active"],
            ["name" => "Reactivation 7d", "trigger_event" => null, "priority" => 70, "status" => "active"],
            ["name" => "Reactivation 14d", "trigger_event" => null, "priority" => 75, "status" => "active"],
            ["name" => "Weekly Recap", "trigger_event" => "scheduled", "priority" => 40, "status" => "active"],
            ["name" => "Streak Recovery", "trigger_event" => "streak.broken", "priority" => 80, "status" => "active"],
            ["name" => "Milestone Celebration", "trigger_event" => "chatter.level_up", "priority" => 85, "status" => "active"],
            ["name" => "Referral Program", "trigger_event" => "chatter.sale_completed", "priority" => 50, "status" => "draft"],
            ["name" => "Flash Bonus", "trigger_event" => null, "priority" => 60, "status" => "draft"],
            ["name" => "Telegram Migration", "trigger_event" => "chatter.registered", "priority" => 88, "status" => "active"],
        ];
        foreach ($sequences as $s) {
            Sequence::firstOrCreate(["name" => $s["name"]], $s);
        }
    }
}
