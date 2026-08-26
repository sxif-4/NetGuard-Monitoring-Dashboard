<?php

namespace App\Jobs;

use App\Enums\AlertSeverity;
use App\Models\Alert;
use App\Models\NotificationSetting;
use App\Services\Notifications\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramAlertNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Transient Telegram API failures are worth a couple of retries, but
     * not indefinitely -- an alert that's still open will just get
     * re-notified on its next natural breach anyway.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * @var array<int, int>
     */
    public $backoff = [10, 30, 60];

    /**
     * Takes the alert's id, not the model, so the queued payload stays a
     * plain integer rather than a serialized Eloquent snapshot that could
     * go stale by the time the job actually runs.
     *
     * $recovered distinguishes a "this just started" push from a "this just
     * cleared" push for the *same* alert row -- ThresholdAlertEvaluator
     * dispatches this a second time, with recovered: true, once a
     * previously-offline device answers again.
     */
    public function __construct(public readonly int $alertId, public readonly bool $recovered = false) {}

    public function handle(TelegramNotifier $notifier): void
    {
        $alert = Alert::find($this->alertId);

        if ($alert === null) {
            return;
        }

        $settings = NotificationSetting::current();
        $token = $settings->effectiveTelegramBotToken();
        $chatId = $settings->effectiveTelegramChatId();

        if (! $token || ! $chatId) {
            return;
        }

        $notifier->send($token, $chatId, $this->formatMessage($alert));
    }

    private function formatMessage(Alert $alert): string
    {
        // Not `$alert->device?->name ?? 'Network-wide'` -- this project's
        // phpstan setup has a confirmed false-positive bug (nullsafe.
        // neverNull) that misfires on *any* `X?->prop ?? default`
        // expression, regardless of whether X is actually nullable
        // (reproduced with a minimal, Eloquent-free example). The
        // equivalent explicit null check below is semantically identical
        // and avoids that specific syntactic pattern entirely.
        $device = $alert->device;
        $deviceName = $device !== null ? $device->name : 'Network-wide';

        if ($this->recovered) {
            return sprintf("%s\n*%s* | ✅ Up", $alert->category->label(), $deviceName);
        }

        return sprintf(
            "%s\n*%s* | %s %s",
            $alert->category->label(),
            $deviceName,
            $this->severityIcon($alert->severity),
            $alert->title,
        );
    }

    private function severityIcon(AlertSeverity $severity): string
    {
        return match ($severity) {
            AlertSeverity::Critical => '🔴',
            AlertSeverity::High => '🟠',
            AlertSeverity::Medium => '🟡',
            AlertSeverity::Low => '🔵',
            AlertSeverity::Informational => 'ℹ️',
        };
    }
}
