<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitoringDevicesEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/monitoring/devices')->assertUnauthorized();
    }

    public function test_a_token_without_the_monitoring_ability_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['some-other-ability']);

        $this->getJson('/api/monitoring/devices')->assertForbidden();
    }

    public function test_it_returns_only_monitoring_enabled_devices(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['monitoring:write']);

        $enabled = Device::factory()->create(['monitoring_enabled' => true]);
        Device::factory()->create(['monitoring_enabled' => false]);

        $response = $this->getJson('/api/monitoring/devices');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $enabled->id);
        $response->assertJsonPath('data.0.ip_address', $enabled->ip_address);
    }

    public function test_the_device_payload_shape_includes_snmp_fields(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['monitoring:write']);

        Device::factory()->create([
            'monitoring_enabled' => true,
            'snmp_community' => 'public',
            'snmp_version' => '2c',
            'snmp_port' => 161,
        ]);

        $this->getJson('/api/monitoring/devices')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'ip_address', 'monitoring_method', 'monitoring_port', 'snmp_community', 'snmp_version', 'snmp_port'],
                ],
            ]);
    }
}
