<?php

namespace Tests\Feature\Console;

use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneDeviceMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_rows_older_than_the_retention_period_and_keeps_recent_ones(): void
    {
        config(['monitoring.metrics_retention_days' => 35]);

        $device = Device::factory()->create();

        $old = DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'polled_at' => now()->subDays(40),
        ]);

        $recent = DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'polled_at' => now()->subDays(10),
        ]);

        $this->artisan('metrics:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_it_respects_a_custom_retention_period(): void
    {
        config(['monitoring.metrics_retention_days' => 5]);

        $device = Device::factory()->create();

        $withinRetention = DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'polled_at' => now()->subDays(3),
        ]);

        $outsideRetention = DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'polled_at' => now()->subDays(10),
        ]);

        $this->artisan('metrics:prune');

        $this->assertModelExists($withinRetention);
        $this->assertModelMissing($outsideRetention);
    }
}
