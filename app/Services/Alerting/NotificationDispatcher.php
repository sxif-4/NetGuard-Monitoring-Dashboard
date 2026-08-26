<?php

namespace App\Services\Alerting;

use App\Enums\AlertSeverity;
use App\Jobs\SendTelegramAlertNotification;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class NotificationDispatcher
{
    /**
     * Per-alert cooldown throttles repeats of the *same* condition, but a
     * flood of distinct new conditions (e.g. security alerts keyed by
     * source_ip) each start with a null last_notified_at and would sail
     * straight through that check. This second, global cap bounds total
     * Telegram/Email volume regardless of how many distinct alerts fire.
     */
    private const GLOBAL_MAX_PER_MINUTE = 4;

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

        // Same condition recurring on *one* alert is already covered by the
        // cooldown above. This catches the other flood shape: many distinct
        // new alerts of the same type (e.g. a port-scan burst keyed by
        // source_ip) that each start with a null last_notified_at and would
        // otherwise sail straight through it one-by-one.
        if ($this->withinTypeCooldown($alert, $settings)) {
            return;
        }

        if (RateLimiter::tooManyAttempts('notifications:global', self::GLOBAL_MAX_PER_MINUTE)) {
            return;
        }

        RateLimiter::hit('notifications:global', 60);

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
     * Called once by ThresholdAlertEvaluator when a device that had an open
     * device_offline alert answers again. Telegram only -- the alert email
     * template is written for "this just started", not "this just cleared",
     * and building a second template wasn't asked for. Still goes through
     * the global cap (a mass recovery, e.g. after a switch reboot, is the
     * same burst risk as a mass outage) but skips the per-alert/per-type
     * cooldowns: a recovery is a one-shot event for an alert that's about
     * to be resolved, not a recurring condition to throttle.
     */
    public function notifyRecovery(Alert $alert): void
    {
        if (! $this->channelsFor($alert->severity)['telegram']) {
            return;
        }

        $settings = NotificationSetting::current();

        if (! $settings->telegram_enabled || ! $settings->effectiveTelegramBotToken() || ! $settings->effectiveTelegramChatId()) {
            return;
        }

        if (RateLimiter::tooManyAttempts('notifications:global', self::GLOBAL_MAX_PER_MINUTE)) {
            return;
        }

        RateLimiter::hit('notifications:global', 60);

        SendTelegramAlertNotification::dispatch($alert->id, recovered: true);
    }

    /**
     * Telegram now covers every severity -- Low/Medium/Informational aren't
     * urgent enough for email, but the user wants them visible on Telegram
     * too, not dashboard-only. Email stays reserved for Critical, since it's
     * a heavier-weight channel.
     *
     * @return array{telegram: bool, email: bool}
     */
    private function channelsFor(AlertSeverity $severity): array
    {
        return match ($severity) {
            AlertSeverity::Critical => ['telegram' => true, 'email' => true],
            default => ['telegram' => true, 'email' => false],
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

    /**
     * At most one notification per (category, type) pair per cooldown
     * window, regardless of which specific alert triggered it. Coarser than
     * the per-alert cooldown on purpose -- a burst of otherwise-distinct
     * alerts sharing a type is still one incident worth one ping, not one
     * ping each.
     */
    private function withinTypeCooldown(Alert $alert, NotificationSetting $settings): bool
    {
        $key = "notifications:type:{$alert->category->value}:{$alert->type}";

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return true;
        }

        RateLimiter::hit($key, $settings->effectiveCooldownSeconds());

        return false;
    }
}
