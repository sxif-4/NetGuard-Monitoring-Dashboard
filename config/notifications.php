<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Cooldown
    |--------------------------------------------------------------------------
    |
    | Default seconds between repeat Telegram/Email notifications for the
    | same open alert, used only when NotificationSetting's DB-backed
    | cooldown_seconds hasn't been set (falsy/zero). Editable per-app from
    | Settings -> Notifications without touching this file.
    |
    */

    'cooldown_seconds' => (int) env('NOTIFICATION_COOLDOWN_SECONDS', 60),

];
