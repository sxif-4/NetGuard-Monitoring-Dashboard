<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Notifications;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationSettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('notifications.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_the_page_shows_current_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        NotificationSetting::current()->update([
            'telegram_enabled' => true,
            'telegram_chat_id' => '-100123456',
            'cooldown_seconds' => 90,
        ]);

        $this->get(route('notifications.edit'))
            ->assertOk()
            ->assertSee('-100123456')
            ->assertSee('90');
    }

    public function test_settings_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Notifications::class)
            ->set('telegramEnabled', true)
            ->set('telegramBotToken', '123:abc')
            ->set('telegramChatId', '-100123456')
            ->set('emailEnabled', true)
            ->set('notificationEmail', 'admin@example.com')
            ->set('cooldownSeconds', 120)
            ->call('save')
            ->assertHasNoErrors();

        $settings = NotificationSetting::current();

        $this->assertTrue($settings->telegram_enabled);
        $this->assertSame('123:abc', $settings->telegram_bot_token);
        $this->assertSame('-100123456', $settings->telegram_chat_id);
        $this->assertTrue($settings->email_enabled);
        $this->assertSame('admin@example.com', $settings->notification_email);
        $this->assertSame(120, $settings->cooldown_seconds);
    }

    public function test_telegram_fields_are_required_when_telegram_is_enabled(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Notifications::class)
            ->set('telegramEnabled', true)
            ->set('telegramBotToken', '')
            ->set('telegramChatId', '')
            ->call('save')
            ->assertHasErrors(['telegramBotToken', 'telegramChatId']);
    }

    public function test_notification_email_must_be_a_valid_email_when_email_is_enabled(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Notifications::class)
            ->set('emailEnabled', true)
            ->set('notificationEmail', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['notificationEmail']);
    }

    public function test_cooldown_seconds_must_stay_within_bounds(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Notifications::class)
            ->set('cooldownSeconds', 5)
            ->call('save')
            ->assertHasErrors(['cooldownSeconds']);

        Livewire::test(Notifications::class)
            ->set('cooldownSeconds', 9999)
            ->call('save')
            ->assertHasErrors(['cooldownSeconds']);
    }

    public function test_the_test_connection_button_reports_success(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->actingAs(User::factory()->create());

        Livewire::test(Notifications::class)
            ->set('telegramBotToken', '123:abc')
            ->set('telegramChatId', '-100123456')
            ->call('testTelegramConnection')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123:abc/sendMessage');
    }

    public function test_the_test_connection_button_requires_both_fields(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Notifications::class)
            ->set('telegramBotToken', '')
            ->set('telegramChatId', '')
            ->call('testTelegramConnection')
            ->assertHasErrors(['telegramBotToken', 'telegramChatId']);
    }
}
