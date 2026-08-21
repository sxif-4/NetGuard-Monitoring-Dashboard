<?php

namespace Tests\Feature\Alerts;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Livewire\Alerts\Index;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlertManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('alerts.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_see_seeded_alerts(): void
    {
        $this->actingAs(User::factory()->create());

        $alert = Alert::factory()->create(['title' => 'High CPU Usage']);

        Livewire::test(Index::class)->assertSee($alert->title);
    }

    public function test_alerts_can_be_filtered_by_status(): void
    {
        $this->actingAs(User::factory()->create());

        $active = Alert::factory()->create(['title' => 'active-alert', 'status' => AlertStatus::Active]);
        $resolved = Alert::factory()->resolved()->create(['title' => 'resolved-alert']);

        Livewire::test(Index::class)
            ->set('statusFilter', AlertStatus::Active->value)
            ->assertSee($active->title)
            ->assertDontSee($resolved->title);
    }

    public function test_alerts_can_be_filtered_by_severity(): void
    {
        $this->actingAs(User::factory()->create());

        $critical = Alert::factory()->create(['title' => 'critical-alert', 'severity' => AlertSeverity::Critical]);
        $low = Alert::factory()->create(['title' => 'low-alert', 'severity' => AlertSeverity::Low]);

        Livewire::test(Index::class)
            ->set('severityFilter', AlertSeverity::Critical->value)
            ->assertSee($critical->title)
            ->assertDontSee($low->title);
    }

    public function test_an_active_alert_can_be_acknowledged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $alert = Alert::factory()->create(['status' => AlertStatus::Active]);

        Livewire::test(Index::class)->call('acknowledge', $alert->id);

        $alert->refresh();

        $this->assertSame(AlertStatus::Acknowledged, $alert->status);
        $this->assertSame($user->id, $alert->acknowledged_by);
        $this->assertNotNull($alert->acknowledged_at);
    }

    public function test_an_acknowledged_alert_can_be_marked_investigating(): void
    {
        $this->actingAs(User::factory()->create());

        $alert = Alert::factory()->acknowledged()->create();

        Livewire::test(Index::class)->call('investigate', $alert->id);

        $this->assertSame(AlertStatus::Investigating, $alert->fresh()->status);
    }

    public function test_an_investigating_alert_can_be_resolved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $alert = Alert::factory()->investigating()->create();

        Livewire::test(Index::class)->call('resolve', $alert->id);

        $alert->refresh();

        $this->assertSame(AlertStatus::Resolved, $alert->status);
        $this->assertSame($user->id, $alert->resolved_by);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_a_detected_alert_cannot_be_directly_resolved(): void
    {
        $this->actingAs(User::factory()->create());

        $alert = Alert::factory()->create(['status' => AlertStatus::Detected]);

        Livewire::test(Index::class)->call('resolve', $alert->id);

        $this->assertSame(AlertStatus::Detected, $alert->fresh()->status);
    }

    public function test_a_resolved_alert_cannot_be_acknowledged_again(): void
    {
        $this->actingAs(User::factory()->create());

        $alert = Alert::factory()->resolved()->create();
        $resolvedAt = $alert->resolved_at;

        Livewire::test(Index::class)->call('acknowledge', $alert->id);

        $alert->refresh();

        $this->assertSame(AlertStatus::Resolved, $alert->status);
        $this->assertEquals($resolvedAt, $alert->resolved_at);
    }
}
