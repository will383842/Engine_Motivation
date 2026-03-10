<?php
declare(strict_types=1);
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            SettingsSeeder::class,
            BadgeSeeder::class,
            MissionSeeder::class,
            SequenceSeeder::class,
            AdminUserSeeder::class,
            MessageTemplateSeeder::class,
        ]);
        if (app()->environment("local", "testing")) {
            \App\Models\Chatter::factory(50)->create();
        }
    }
}
