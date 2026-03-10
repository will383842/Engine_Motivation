<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\Setting;
use Illuminate\Database\Seeder;
class SettingsSeeder extends Seeder {
    public function run(): void {
        $settings = [
            ["key" => "streak_freeze_cost_cents", "value" => 500, "description" => "Cost in cents to freeze a streak"],
            ["key" => "streak_max_freezes", "value" => 3, "description" => "Max streak freezes per period"],
            ["key" => "xp_per_sale", "value" => 50, "description" => "XP earned per sale"],
            ["key" => "xp_per_referral", "value" => 100, "description" => "XP earned per referral"],
            ["key" => "min_withdrawal_cents", "value" => 3000, "description" => "Minimum withdrawal amount"],
            ["key" => "whatsapp_daily_budget_cents", "value" => 5000, "description" => "Daily WhatsApp spending cap"],
            ["key" => "telegram_migration_cta", "value" => true, "description" => "Include Telegram CTA in WhatsApp messages"],
            ["key" => "fatigue_threshold_high", "value" => 60, "description" => "Fatigue score threshold for high fatigue"],
            ["key" => "fatigue_threshold_critical", "value" => 80, "description" => "Fatigue score threshold for critical"],
        ];
        foreach ($settings as $s) {
            Setting::firstOrCreate(["key" => $s["key"]], ["value" => json_encode($s["value"]), "description" => $s["description"]]);
        }
    }
}
