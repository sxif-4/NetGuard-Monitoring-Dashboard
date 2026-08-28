<?php

namespace Tests\Feature\Devices;

use App\Livewire\Devices\Show;
use App\Models\Device;
use App\Models\DeviceMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $device = Device::factory()->create();

        $response = $this->get(route('devices.show', $device));
        $response->assertRedirect(route('login'));
    }

    public function test_the_page_renders_for_a_device_with_no_metrics(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $device = Device::factory()->create();

        $response = $this->get(route('devices.show', $device));

        $response->assertOk();
        $response->assertSee($device->name);
        $response->assertSee('CPU Usage');
    }

    public function test_the_page_renders_for_a_device_with_metrics(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $device = Device::factory()->create();

        DeviceMetric::factory()->count(5)->create([
            'device_id' => $device->id,
            'polled_at' => now()->subMinutes(10),
        ]);

        $response = $this->get(route('devices.show', $device));

        $response->assertOk();
    }

    public function test_switching_range_updates_the_series(): void
    {
        $this->actingAs(User::factory()->create());

        $device = Device::factory()->create();

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 42.0,
            'polled_at' => now()->subMinutes(5),
        ]);

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 12.0,
            'polled_at' => now()->subDays(10),
        ]);

        $component = Livewire::test(Show::class, ['device' => $device]);

        $oneHourSeries = $component->instance()->series();
        $this->assertCount(1, $oneHourSeries);

        $component->set('range', '30d');

        $thirtyDaySeries = $component->instance()->series();
        $this->assertCount(2, $thirtyDaySeries);
    }
}
