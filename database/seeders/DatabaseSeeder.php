<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\MonitoringMethod;
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

        // Bulk demo devices for the CRUD screen — fake IPs, so monitoring is left off
        // to keep the Phase 2 daemon from wasting a cycle timing out on unreachable hosts.
        $devices = collect([
            ...Device::factory()->count(10)->create(['monitoring_enabled' => false]),
            ...Device::factory()->count(2)->warning()->create(['monitoring_enabled' => false]),
            ...Device::factory()->count(1)->offline()->create(['monitoring_enabled' => false]),
            ...Device::factory()->count(1)->monitoringDisabled()->create(),
        ]);

        // Real targets the Phase 2 daemon actually polls, so the live dashboard has
        // genuine online/offline data instead of everything sitting at "unknown".
        $liveDevices = collect([
            Device::factory()->create([
                'name' => 'localhost',
                'type' => DeviceType::Server,
                'ip_address' => '127.0.0.1',
                'monitoring_method' => MonitoringMethod::Snmp,
                'snmp_community' => 'public',
                'monitoring_enabled' => true,
                'status' => DeviceStatus::Unknown,
                'last_seen_at' => null,
            ]),
            Device::factory()->create([
                'name' => 'cloudflare-dns',
                'type' => DeviceType::Other,
                'ip_address' => '1.1.1.1',
                'monitoring_method' => MonitoringMethod::Icmp,
                'monitoring_enabled' => true,
                'status' => DeviceStatus::Unknown,
                'last_seen_at' => null,
            ]),
            Device::factory()->create([
                'name' => 'google-dns',
                'type' => DeviceType::Other,
                'ip_address' => '8.8.8.8',
                'monitoring_method' => MonitoringMethod::Icmp,
                'monitoring_enabled' => true,
                'status' => DeviceStatus::Unknown,
                'last_seen_at' => null,
            ]),
            Device::factory()->create([
                'name' => 'unreachable-test',
                'type' => DeviceType::Other,
                'ip_address' => '10.255.255.1',
                'monitoring_method' => MonitoringMethod::Icmp,
                'monitoring_enabled' => true,
                'status' => DeviceStatus::Unknown,
                'last_seen_at' => null,
            ]),
        ]);

        $devices = $devices->merge($liveDevices);

        Alert::factory()->recycle($devices)->count(20)->create();
        Alert::factory()->recycle($devices)->count(3)->create([
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::Active,
        ]);

        $this->call(ThresholdSeeder::class);
    }
}
