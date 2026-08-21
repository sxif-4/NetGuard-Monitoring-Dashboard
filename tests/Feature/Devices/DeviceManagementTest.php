<?php

namespace Tests\Feature\Devices;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\MonitoringMethod;
use App\Livewire\Devices\Index;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('devices.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_see_seeded_devices(): void
    {
        $this->actingAs(User::factory()->create());

        $device = Device::factory()->create(['name' => 'core-switch-01']);

        Livewire::test(Index::class)
            ->assertSee($device->name);
    }

    public function test_a_device_can_be_created(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'edge-router-01')
            ->set('type', DeviceType::Router->value)
            ->set('ipAddress', '10.0.0.1')
            ->set('monitoringMethod', MonitoringMethod::Icmp->value)
            ->set('location', 'HQ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('devices', [
            'name' => 'edge-router-01',
            'ip_address' => '10.0.0.1',
        ]);
    }

    public function test_creating_a_device_requires_a_unique_ip_address(): void
    {
        $this->actingAs(User::factory()->create());

        Device::factory()->create(['ip_address' => '10.0.0.5']);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'duplicate-ip-device')
            ->set('type', DeviceType::Server->value)
            ->set('ipAddress', '10.0.0.5')
            ->set('monitoringMethod', MonitoringMethod::Icmp->value)
            ->call('save')
            ->assertHasErrors(['ipAddress']);
    }

    public function test_a_device_can_be_edited(): void
    {
        $this->actingAs(User::factory()->create());

        $device = Device::factory()->create(['name' => 'old-name', 'ip_address' => '10.0.0.10']);

        Livewire::test(Index::class)
            ->call('edit', $device->id)
            ->set('name', 'new-name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('new-name', $device->fresh()->name);
    }

    public function test_editing_a_device_ignores_its_own_ip_address_in_the_uniqueness_check(): void
    {
        $this->actingAs(User::factory()->create());

        $device = Device::factory()->create(['ip_address' => '10.0.0.20']);

        Livewire::test(Index::class)
            ->call('edit', $device->id)
            ->set('ipAddress', '10.0.0.20')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_a_device_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $device = Device::factory()->create();

        Livewire::test(Index::class)
            ->call('confirmDelete', $device->id)
            ->call('delete');

        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_monitoring_can_be_toggled(): void
    {
        $this->actingAs(User::factory()->create());

        $device = Device::factory()->create(['monitoring_enabled' => true]);

        Livewire::test(Index::class)
            ->call('toggleMonitoring', $device->id);

        $this->assertFalse($device->fresh()->monitoring_enabled);
    }

    public function test_devices_can_be_filtered_by_status(): void
    {
        $this->actingAs(User::factory()->create());

        $online = Device::factory()->create(['name' => 'online-device', 'status' => DeviceStatus::Online]);
        $offline = Device::factory()->create(['name' => 'offline-device', 'status' => DeviceStatus::Offline]);

        Livewire::test(Index::class)
            ->set('statusFilter', DeviceStatus::Offline->value)
            ->assertSee($offline->name)
            ->assertDontSee($online->name);
    }
}
