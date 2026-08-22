<?php

namespace Tests\Feature\Jobs;

use App\Enums\AlertSeverity;
use App\Jobs\SendTelegramAlertNotification;
use App\Models\Alert;
use App\Models\NotificationSetting;
use App\Services\Notifications\TelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendTelegramAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_real_telegram_request_using_the_stored_credentials(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        NotificationSetting::current()->update([
            'telegram_bot_token' => 'stored-token',
            'telegram_chat_id' => 'stored-chat-id',
        ]);

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::Critical,
            'title' => 'Device Offline',
            'occurrences' => 3,
        ]);

        (new SendTelegramAlertNotification($alert->id))->handle(app(TelegramNotifier::class));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/botstored-token/sendMessage'
            && $request['chat_id'] === 'stored-chat-id'
            && str_contains($request['text'], 'Device Offline'));
    }

    public function test_it_does_nothing_if_the_alert_no_longer_exists(): void
    {
        Http::fake();

        NotificationSetting::current()->update([
            'telegram_bot_token' => 'stored-token',
            'telegram_chat_id' => 'stored-chat-id',
        ]);

        (new SendTelegramAlertNotification(999999))->handle(app(TelegramNotifier::class));

        Http::assertNothingSent();
    }

    public function test_it_does_nothing_if_no_bot_token_or_chat_id_is_configured(): void
    {
        Http::fake();

        $alert = Alert::factory()->create(['severity' => AlertSeverity::Critical]);

        (new SendTelegramAlertNotification($alert->id))->handle(app(TelegramNotifier::class));

        Http::assertNothingSent();
    }
}
