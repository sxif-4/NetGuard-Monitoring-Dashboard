<?php

namespace Tests\Feature\Api;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitoringMetricsEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['monitoring:write']);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->postJson('/api/monitoring/metrics', ['metrics' => []])->assertUnauthorized();
    }

    public function test_it_requires_at_least_one_metric_entry(): void
    {
        $this->authenticate();

        $this->postJson('/api/monitoring/metrics', ['metrics' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metrics']);
    }

    public function test_a_reachable_result_marks_the_device_online_and_bumps_last_seen(): void
    {
        $this->authenticate();

        $device = Device::factory()->create(['status' => DeviceStatus::Unknown, 'last_seen_at' => null]);

        $response = $this->postJson('/api/monitoring/metrics', [
            'metrics' => [[
                'device_id' => $device->id,
                'reachable' => true,
                'polled_at' => '2026-08-22T10:00:00Z',
                'latency_ms' => 12.5,
                'packet_loss_pct' => 0,
                'cpu_pct' => 40,
                'memory_pct' => 55,
                'uptime_seconds' => 3600,
            ]],
        ]);

        $response->assertCreated();
        $response->assertJson(['accepted' => 1]);

        $device->refresh();

        $this->assertSame(DeviceStatus::Online, $device->status);
        $this->assertNotNull($device->last_seen_at);
        $this->assertDatabaseHas('device_metrics', [
            'device_id' => $device->id,
            'reachable' => true,
            'cpu_pct' => 40,
        ]);
    }

    public function test_an_unreachable_result_marks_the_device_offline_without_bumping_last_seen(): void
    {
        $this->authenticate();

        $lastSeen = now()->subHour();
        $device = Device::factory()->create(['status' => DeviceStatus::Online, 'last_seen_at' => $lastSeen]);

        $this->postJson('/api/monitoring/metrics', [
            'metrics' => [[
                'device_id' => $device->id,
                'reachable' => false,
                'polled_at' => now()->toIso8601String(),
                'error' => 'timeout',
            ]],
        ])->assertCreated();

        $device->refresh();

        $this->assertSame(DeviceStatus::Offline, $device->status);
        $this->assertEquals($lastSeen->timestamp, $device->last_seen_at->timestamp);
    }

    public function test_an_unknown_device_id_is_skipped_gracefully(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/monitoring/metrics', [
            'metrics' => [[
                'device_id' => 999999,
                'reachable' => true,
                'polled_at' => now()->toIso8601String(),
            ]],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['metrics.0.device_id']);
    }
}
