<?php

namespace Tests\Feature\Api;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Models\Device;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityAlertingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create(), ['monitoring:write']);
    }

    private function postSecurityEvent(array $overrides = []): void
    {
        $this->postJson('/api/monitoring/security-events', [
            'events' => [array_merge([
                'type' => 'port_scan',
                'source_ip' => '192.168.1.24',
                'destination_ip' => '10.0.0.5',
                'source_port' => 51000,
                'destination_port' => 22,
                'protocol' => 'TCP',
                'model' => 'isolation_forest',
                'anomaly_score' => 87.0,
                'possible_activity' => 'Port Scanning',
                'detected_at' => now()->toIso8601String(),
            ], $overrides)],
        ])->assertCreated();
    }

    public function test_a_new_source_ip_creates_a_security_alert(): void
    {
        $this->postSecurityEvent();

        $this->assertDatabaseHas('alerts', [
            'category' => 'security',
            'type' => 'port_scan',
            'severity' => AlertSeverity::Critical->value,
            'status' => AlertStatus::Active->value,
            'occurrences' => 1,
        ]);
    }

    public function test_a_repeat_event_from_the_same_ip_updates_the_existing_alert(): void
    {
        $this->postSecurityEvent();
        $this->postSecurityEvent(['anomaly_score' => 90.0]);

        $this->assertSame(1, Alert::where('type', 'port_scan')->count());

        $alert = Alert::where('type', 'port_scan')->first();
        $this->assertSame(2, $alert->occurrences);
        $this->assertEquals(90.0, $alert->metadata['anomaly_score']);
    }

    public function test_device_id_is_populated_when_the_source_ip_matches_a_monitored_device(): void
    {
        $device = Device::factory()->create(['ip_address' => '192.168.1.24']);

        $this->postSecurityEvent();

        $alert = Alert::where('type', 'port_scan')->first();
        $this->assertSame($device->id, $alert->device_id);
    }

    public function test_device_id_stays_null_when_the_ip_matches_no_monitored_device(): void
    {
        $this->postSecurityEvent();

        $alert = Alert::where('type', 'port_scan')->first();
        $this->assertNull($alert->device_id);
    }

    public function test_a_score_below_the_low_threshold_creates_no_alert(): void
    {
        $this->postSecurityEvent(['anomaly_score' => 10.0]);

        $this->assertDatabaseMissing('alerts', ['type' => 'port_scan']);
    }

    public function test_a_new_event_after_manual_resolution_creates_a_fresh_alert(): void
    {
        $this->postSecurityEvent();

        $alert = Alert::where('type', 'port_scan')->first();
        $alert->update(['status' => AlertStatus::Resolved, 'resolved_at' => now()]);

        $this->postSecurityEvent();

        $this->assertSame(2, Alert::where('type', 'port_scan')->count());

        $newAlert = Alert::where('type', 'port_scan')
            ->where('status', '!=', AlertStatus::Resolved)
            ->first();

        $this->assertNotNull($newAlert);
        $this->assertSame(1, $newAlert->occurrences);
    }

    public function test_an_invalid_type_is_rejected(): void
    {
        $this->postJson('/api/monitoring/security-events', [
            'events' => [[
                'type' => 'not-a-real-type',
                'source_ip' => '192.168.1.24',
                'protocol' => 'TCP',
                'model' => 'isolation_forest',
                'anomaly_score' => 87.0,
                'possible_activity' => 'Port Scanning',
                'detected_at' => now()->toIso8601String(),
            ]],
        ])->assertUnprocessable();
    }

    public function test_alert_metadata_captures_the_flagged_flow_details(): void
    {
        $this->postSecurityEvent();

        $alert = Alert::where('type', 'port_scan')->first();

        $this->assertSame('192.168.1.24', $alert->metadata['source_ip']);
        $this->assertSame('10.0.0.5', $alert->metadata['destination_ip']);
        $this->assertSame('TCP', $alert->metadata['protocol']);
        $this->assertSame('isolation_forest', $alert->metadata['model']);
        $this->assertEquals(87.0, $alert->metadata['anomaly_score']);
    }

    public function test_a_critical_security_event_queues_an_email_when_enabled(): void
    {
        Mail::fake();
        NotificationSetting::current()->update([
            'email_enabled' => true,
            'notification_email' => 'admin@example.com',
        ]);

        $this->postSecurityEvent(['anomaly_score' => 90.0]);

        $alert = Alert::where('type', 'port_scan')->first();

        Mail::assertQueued(AlertNotificationMail::class, fn ($mail) => $mail->alert->is($alert));
    }
}
