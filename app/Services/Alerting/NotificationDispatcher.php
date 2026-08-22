<?php

namespace App\Services\Alerting;

use App\Enums\AlertSeverity;
use App\Jobs\SendTelegramAlertNotification;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    /**
     * Called once by ThresholdAlertEvaluator/SecurityAlertEvaluator right
     * after they create-or-update an Alert. Routes by severity (plan.md
     * §11), respects the per-alert cooldown so a condition that stays
     * breached for many poll cycles doesn't spam Telegram/Email every
     * single one, and only bumps last_notified_at when something was
     * actually queued -- Medium/Low/Informational alerts have nothing to
     * cool down for, since they're dashboard-only.
     */
    public function dispatch(Alert $alert): void
    {
        $channels = $this->channelsFor($alert->severity);

        if (! $channels['telegram'] && ! $channels['email']) {
            return;
        }

        $settings = NotificationSetting::current();

        if ($this->withinCooldown($alert, $settings)) {
            return;
        }

        $sent = false;

        if ($channels['telegram'] && $settings->telegram_enabled
            && $settings->effectiveTelegramBotToken() && $settings->effectiveTelegramChatId()) {
            SendTelegramAlertNotification::dispatch($alert->id);
            $sent = true;
        }

        if ($channels['email'] && $settings->email_enabled && $settings->notification_email) {
            Mail::to($settings->notification_email)->queue(new AlertNotificationMail($alert));
            $sent = true;
        }

        if ($sent) {
            $alert->update(['last_notified_at' => now()]);
        }
    }

    /**
     * @return array{telegram: bool, email: bool}
     */
    private function channelsFor(AlertSeverity $severity): array
    {
        return match ($severity) {
            AlertSeverity::Critical => ['telegram' => true, 'email' => true],
            AlertSeverity::High => ['telegram' => true, 'email' => false],
            default => ['telegram' => false, 'email' => false],
        };
    }

    private function withinCooldown(Alert $alert, NotificationSetting $settings): bool
    {
        if ($alert->last_notified_at === null) {
            return false;
        }

        // diffInSeconds() is signed (negative when the argument is in the
        // past) -- absolute: true avoids depending on argument order.
        return now()->diffInSeconds($alert->last_notified_at, absolute: true) < $settings->effectiveCooldownSeconds();
    }
}
