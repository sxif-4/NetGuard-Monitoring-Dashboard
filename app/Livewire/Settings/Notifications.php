<?php

namespace App\Livewire\Settings;

use App\Models\NotificationSetting;
use App\Services\Notifications\TelegramNotifier;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Notification settings')]
class Notifications extends Component
{
    public bool $telegramEnabled = false;

    public ?string $telegramBotToken = null;

    public ?string $telegramChatId = null;

    public bool $emailEnabled = false;

    public ?string $notificationEmail = null;

    public int $cooldownSeconds = 60;

    public function mount(): void
    {
        $settings = NotificationSetting::current();

        $this->telegramEnabled = $settings->telegram_enabled;
        $this->telegramBotToken = $settings->telegram_bot_token;
        $this->telegramChatId = $settings->telegram_chat_id;
        $this->emailEnabled = $settings->email_enabled;
        $this->notificationEmail = $settings->notification_email;
        $this->cooldownSeconds = $settings->cooldown_seconds;
    }

    public function save(): void
    {
        $this->validate([
            'telegramBotToken' => ['required_if:telegramEnabled,true', 'nullable', 'string'],
            'telegramChatId' => ['required_if:telegramEnabled,true', 'nullable', 'string'],
            'notificationEmail' => ['required_if:emailEnabled,true', 'nullable', 'email'],
            'cooldownSeconds' => ['required', 'integer', 'min:10', 'max:3600'],
        ]);

        NotificationSetting::current()->update([
            'telegram_enabled' => $this->telegramEnabled,
            'telegram_bot_token' => $this->telegramBotToken,
            'telegram_chat_id' => $this->telegramChatId,
            'email_enabled' => $this->emailEnabled,
            'notification_email' => $this->notificationEmail,
            'cooldown_seconds' => $this->cooldownSeconds,
        ]);

        Flux::toast(variant: 'success', text: __('Notification settings updated.'));
    }

    /**
     * Sends a real test message using the *unsaved* form values, so the
     * user can confirm a bot token/chat ID works before saving it.
     */
    public function testTelegramConnection(TelegramNotifier $notifier): void
    {
        $this->validate([
            'telegramBotToken' => ['required', 'string'],
            'telegramChatId' => ['required', 'string'],
        ]);

        $sent = $notifier->send(
            $this->telegramBotToken,
            $this->telegramChatId,
            __('✅ NetGuard test notification — your Telegram bot is connected.'),
        );

        Flux::toast(
            variant: $sent ? 'success' : 'danger',
            text: $sent
                ? __('Test message sent — check your Telegram chat.')
                : __('Failed to send test message. Check the bot token and chat ID.'),
        );
    }

    public function render(): View
    {
        return view('livewire.settings.notifications');
    }
}
