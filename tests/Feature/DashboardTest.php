<?php

namespace Tests\Feature;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Livewire\Dashboard;
use App\Models\Alert;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Overview');
    }

    public function test_device_counts_reflect_seeded_devices(): void
    {
        $this->actingAs(User::factory()->create());

        Device::factory()->count(3)->create(['status' => DeviceStatus::Online]);
        Device::factory()->count(2)->create(['status' => DeviceStatus::Warning]);
        Device::factory()->count(1)->create(['status' => DeviceStatus::Offline]);

        $component = Livewire::test(Dashboard::class);

        $this->assertSame([
            'total' => 6,
            'online' => 3,
            'warning' => 2,
            'offline' => 1,
        ], $component->instance()->deviceCounts());
    }

    public function test_network_health_is_critical_when_a_monitored_device_is_offline(): void
    {
        $this->actingAs(User::factory()->create());

        Device::factory()->create(['status' => DeviceStatus::Offline, 'monitoring_enabled' => true]);

        $component = Livewire::test(Dashboard::class);

        $this->assertSame('critical', $component->instance()->networkHealth());
    }

    public function test_network_health_is_healthy_with_no_devices_or_alerts(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Dashboard::class);

        $this->assertSame('healthy', $component->instance()->networkHealth());
    }

    public function test_recent_alerts_are_ordered_by_last_detected_at(): void
    {
        $this->actingAs(User::factory()->create());

        $older = Alert::factory()->create([
            'last_detected_at' => now()->subHours(3),
            'status' => AlertStatus::Active,
            'severity' => AlertSeverity::Low,
        ]);

        $newer = Alert::factory()->create([
            'last_detected_at' => now()->subMinutes(5),
            'status' => AlertStatus::Active,
            'severity' => AlertSeverity::Low,
        ]);

        $component = Livewire::test(Dashboard::class);

        $recent = $component->instance()->recentAlerts();

        $this->assertSame($newer->id, $recent->first()->id);
        $this->assertSame($older->id, $recent->last()->id);
    }

    public function test_security_summary_counts_unresolved_security_alerts_by_type(): void
    {
        $this->actingAs(User::factory()->create());

        Alert::factory()->create([
            'category' => AlertCategory::Security,
            'type' => 'port_scan',
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::Active,
        ]);
        Alert::factory()->create([
            'category' => AlertCategory::Security,
            'type' => 'dos_activity',
            'severity' => AlertSeverity::High,
            'status' => AlertStatus::Active,
        ]);
        Alert::factory()->create([
            'category' => AlertCategory::Security,
            'type' => 'suspicious_ip',
            'severity' => AlertSeverity::Low,
            'status' => AlertStatus::Active,
        ]);
        // Resolved -- must not be counted.
        Alert::factory()->create([
            'category' => AlertCategory::Security,
            'type' => 'port_scan',
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::Resolved,
        ]);
        // Operational -- must not be counted in the security summary.
        Alert::factory()->create([
            'category' => AlertCategory::Operational,
            'type' => 'high_cpu',
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::Active,
        ]);

        $component = Livewire::test(Dashboard::class);

        $this->assertSame([
            'anomalies_detected' => 3,
            'port_scan_attempts' => 1,
            'dos_activity' => 1,
            'suspicious_behaviour' => 1,
            'high_risk_events' => 2,
        ], $component->instance()->securitySummary());
    }
}
