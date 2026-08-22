<?php

namespace Tests\Feature\Api;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Jobs\SendTelegramAlertNotification;
use App\Models\Alert;
use App\Models\Device;
use App\Models\NotificationSetting;
use App\Models\User;
use Database\Seeders\ThresholdSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThresholdAlertingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ThresholdSeeder::class);
        Sanctum::actingAs(User::factory()->create(), ['monitoring:write']);
    }

    private function postMetric(Device $device, array $overrides = []): void
    {
        $this->postJson('/api/monitoring/metrics', [
            'metrics' => [array_merge([
                'device_id' => $device->id,
                'reachable' => true,
                'polled_at' => now()->toIso8601String(),
            ], $overrides)],
        ])->assertCreated();
    }

    public function test_an_unreachable_device_creates_a_critical_offline_alert(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['reachable' => false, 'error' => 'timeout']);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'type' => 'device_offline',
            'severity' => AlertSeverity::Critical->value,
            'status' => AlertStatus::Active->value,
            'occurrences' => 1,
        ]);
    }

    public function test_a_metric_between_warning_and_critical_creates_a_high_severity_alert(): void
    {
        $device = Device::factory()->create();

        // cpu warning = 75, critical = 90 (ThresholdSeeder defaults)
        $this->postMetric($device, ['cpu_pct' => 80]);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'type' => 'high_cpu',
            'severity' => AlertSeverity::High->value,
        ]);
    }

    public function test_a_metric_at_or_above_critical_creates_a_critical_alert(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 95]);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'type' => 'high_cpu',
            'severity' => AlertSeverity::Critical->value,
        ]);
    }

    public function test_a_metric_below_warning_creates_no_alert(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 10]);

        $this->assertDatabaseMissing('alerts', [
            'device_id' => $device->id,
            'type' => 'high_cpu',
        ]);
    }

    public function test_a_repeated_breach_updates_the_existing_open_alert_instead_of_duplicating(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 80]);
        $this->postMetric($device, ['cpu_pct' => 82]);

        $this->assertSame(1, Alert::where('device_id', $device->id)->where('type', 'high_cpu')->count());

        $alert = Alert::where('device_id', $device->id)->where('type', 'high_cpu')->first();
        $this->assertSame(2, $alert->occurrences);
    }

    public function test_a_worsening_breach_escalates_severity_on_the_open_alert(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 80]); // warning tier -> High
        $this->postMetric($device, ['cpu_pct' => 95]); // critical tier -> Critical

        $alert = Alert::where('device_id', $device->id)->where('type', 'high_cpu')->first();
        $this->assertSame(AlertSeverity::Critical, $alert->severity);
        $this->assertSame(2, $alert->occurrences);
    }

    public function test_a_new_breach_after_manual_resolution_creates_a_fresh_alert(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 80]);

        $alert = Alert::where('device_id', $device->id)->where('type', 'high_cpu')->first();
        $alert->update(['status' => AlertStatus::Resolved, 'resolved_at' => now()]);

        $this->postMetric($device, ['cpu_pct' => 82]);

        $this->assertSame(2, Alert::where('device_id', $device->id)->where('type', 'high_cpu')->count());

        $newAlert = Alert::where('device_id', $device->id)
            ->where('type', 'high_cpu')
            ->where('status', '!=', AlertStatus::Resolved)
            ->first();

        $this->assertNotNull($newAlert);
        $this->assertSame(1, $newAlert->occurrences);
    }

    public function test_an_unreachable_entry_does_not_also_generate_metric_threshold_alerts(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['reachable' => false, 'cpu_pct' => null]);

        $this->assertDatabaseMissing('alerts', ['device_id' => $device->id, 'type' => 'high_cpu']);
        $this->assertDatabaseHas('alerts', ['device_id' => $device->id, 'type' => 'device_offline']);
    }

    public function test_alert_metadata_captures_the_observed_value_and_threshold(): void
    {
        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 95]);

        $alert = Alert::where('device_id', $device->id)->where('type', 'high_cpu')->first();

        // Values round-trip through JSON, where a whole-number float (95.0) encodes
        // as `95` and decodes back as an int — assertEquals rather than assertSame.
        $this->assertEquals(95.0, $alert->metadata['value']);
        $this->assertEquals(90.0, $alert->metadata['threshold']);
        $this->assertSame('%', $alert->metadata['unit']);
    }

    public function test_a_critical_breach_queues_a_telegram_notification_when_enabled(): void
    {
        Queue::fake();
        NotificationSetting::current()->update([
            'telegram_enabled' => true,
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => 'test-chat-id',
        ]);

        $device = Device::factory()->create();

        $this->postMetric($device, ['cpu_pct' => 95]);

        $alert = Alert::where('device_id', $device->id)->where('type', 'high_cpu')->first();

        Queue::assertPushed(
            SendTelegramAlertNotification::class,
            fn ($job) => $job->alertId === $alert->id,
        );
    }
}
