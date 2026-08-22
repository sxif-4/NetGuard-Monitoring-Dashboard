<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    /**
     * Sends a message via the Telegram Bot API. Used by
     * App\Jobs\SendTelegramAlertNotification (queued, real bot
     * token/chat ID from NotificationSetting) and by the Settings ->
     * Notifications "Test connection" button (unsaved form values).
     */
    public function send(string $botToken, string $chatId, string $message): bool
    {
        try {
            $response = Http::asJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);
        } catch (Throwable $exception) {
            Log::warning('Telegram notification request failed', ['error' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Telegram notification rejected', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return $response->successful();
    }
}
