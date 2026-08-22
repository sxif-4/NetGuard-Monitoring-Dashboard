<?php

namespace Tests\Feature\Services\Alerting;

use App\Enums\AlertSeverity;
use App\Jobs\SendTelegramAlertNotification;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Models\NotificationSetting;
use App\Services\Alerting\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private function enableBothChannels(): void
    {
        NotificationSetting::current()->update([
            'telegram_enabled' => true,
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => 'test-chat-id',
            'email_enabled' => true,
            'notification_email' => 'admin@example.com',
            'cooldown_seconds' => 60,
        ]);
    }

    public function test_critical_alert_dispatches_both_telegram_and_email(): void
    {
        Queue::fake();
        Mail::fake();
        $this->enableBothChannels();

        $alert = Alert::factory()->create(['severity' => AlertSeverity::Critical, 'last_notified_at' => null]);

        app(NotificationDispatcher::class)->dispatch($alert);

        Queue::assertPushed(SendTelegramAlertNotification::class, fn ($job) => $job->alertId === $alert->id);
        Mail::assertQueued(AlertNotificationMail::class, fn ($mail) => $mail->alert->is($alert));
        $this->assertNotNull($alert->fresh()->last_notified_at);
    }

    public function test_high_alert_dispatches_telegram_only(): void
    {
        Queue::fake();
        Mail::fake();
        $this->enableBothChannels();

        $alert = Alert::factory()->create(['severity' => AlertSeverity::High, 'last_notified_at' => null]);

        app(NotificationDispatcher::class)->dispatch($alert);

        Queue::assertPushed(SendTelegramAlertNotification::class);
        Mail::assertNothingQueued();
    }

    public function test_medium_low_and_informational_alerts_dispatch_nothing(): void
    {
        Queue::fake();
        Mail::fake();
        $this->enableBothChannels();

        foreach ([AlertSeverity::Medium, AlertSeverity::Low, AlertSeverity::Informational] as $severity) {
            $alert = Alert::factory()->create(['severity' => $severity, 'last_notified_at' => null]);

            app(NotificationDispatcher::class)->dispatch($alert);

            $this->assertNull($alert->fresh()->last_notified_at);
        }

        Queue::assertNothingPushed();
        Mail::assertNothingQueued();
    }

    public function test_a_second_dispatch_within_the_cooldown_window_is_suppressed(): void
    {
        Queue::fake();
        $this->enableBothChannels();

        $alert = Alert::factory()->create(['severity' => AlertSeverity::High, 'last_notified_at' => null]);

        app(NotificationDispatcher::class)->dispatch($alert);
        app(NotificationDispatcher::class)->dispatch($alert->fresh());

        Queue::assertPushed(SendTelegramAlertNotification::class, 1);
    }

    public function test_a_dispatch_after_the_cooldown_expires_fires_again(): void
    {
        Queue::fake();
        $this->enableBothChannels();
        NotificationSetting::current()->update(['cooldown_seconds' => 60]);

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::High,
            'last_notified_at' => now()->subSeconds(120),
        ]);

        app(NotificationDispatcher::class)->dispatch($alert);

        Queue::assertPushed(SendTelegramAlertNotification::class, 1);
    }

    public function test_telegram_disabled_suppresses_telegram_even_for_critical(): void
    {
        Queue::fake();
        Mail::fake();
        $this->enableBothChannels();
        NotificationSetting::current()->update(['telegram_enabled' => false]);

        $alert = Alert::factory()->create(['severity' => AlertSeverity::Critical, 'last_notified_at' => null]);

        app(NotificationDispatcher::class)->dispatch($alert);

        Queue::assertNothingPushed();
        Mail::assertQueued(AlertNotificationMail::class);
    }

    public function test_email_disabled_suppresses_email_even_for_critical(): void
    {
        Queue::fake();
        Mail::fake();
        $this->enableBothChannels();
        NotificationSetting::current()->update(['email_enabled' => false]);

        $alert = Alert::factory()->create(['severity' => AlertSeverity::Critical, 'last_notified_at' => null]);

        app(NotificationDispatcher::class)->dispatch($alert);

        Queue::assertPushed(SendTelegramAlertNotification::class);
        Mail::assertNothingQueued();
    }

    public function test_missing_telegram_credentials_does_not_dispatch_or_crash(): void
    {
        Queue::fake();

        NotificationSetting::current()->update([
            'telegram_enabled' => true,
            'telegram_bot_token' => null,
            'telegram_chat_id' => null,
        ]);

        $alert = Alert::factory()->create(['severity' => AlertSeverity::High, 'last_notified_at' => null]);

        app(NotificationDispatcher::class)->dispatch($alert);

        Queue::assertNothingPushed();
        $this->assertNull($alert->fresh()->last_notified_at);
    }
}
