<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A singleton settings row (unlike Threshold's fixed-set-of-rows-per-metric
 * shape) — one row for the whole app, self-healing via current().
 *
 * @property int $id
 * @property string|null $telegram_bot_token
 * @property string|null $telegram_chat_id
 * @property bool $telegram_enabled
 * @property bool $email_enabled
 * @property string|null $notification_email
 * @property int $cooldown_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['telegram_bot_token', 'telegram_chat_id', 'telegram_enabled', 'email_enabled', 'notification_email', 'cooldown_seconds'])]
class NotificationSetting extends Model
{
    /**
     * Get the app's one notification-settings row, creating it with
     * defaults if it doesn't exist yet (no seeder dependency).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'telegram_enabled' => false,
            'email_enabled' => false,
            'cooldown_seconds' => config('notifications.cooldown_seconds', 60),
        ]);
    }

    /**
     * DB value takes priority; .env is a fallback default only, per the
     * "swap bots without a redeploy" requirement.
     */
    public function effectiveTelegramBotToken(): ?string
    {
        return $this->telegram_bot_token ?: config('services.telegram.bot_token');
    }

    public function effectiveTelegramChatId(): ?string
    {
        return $this->telegram_chat_id ?: config('services.telegram.chat_id');
    }

    public function effectiveCooldownSeconds(): int
    {
        return $this->cooldown_seconds ?: (int) config('notifications.cooldown_seconds', 60);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'cooldown_seconds' => 'integer',
        ];
    }
}
