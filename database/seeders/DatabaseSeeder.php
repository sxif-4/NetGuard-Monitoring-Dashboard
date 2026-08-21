<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $devices = collect([
            ...Device::factory()->count(10)->create(),
            ...Device::factory()->count(2)->warning()->create(),
            ...Device::factory()->count(1)->offline()->create(),
            ...Device::factory()->count(1)->monitoringDisabled()->create(),
        ]);

        Alert::factory()->recycle($devices)->count(20)->create();
        Alert::factory()->recycle($devices)->count(3)->create([
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::Active,
        ]);

        $this->call(ThresholdSeeder::class);
    }
}
