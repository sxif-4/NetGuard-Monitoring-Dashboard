<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Online = 'online';
    case Warning = 'warning';
    case Offline = 'offline';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Warning => 'Warning',
            self::Offline => 'Offline',
            self::Unknown => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Online => 'green',
            self::Warning => 'amber',
            self::Offline => 'red',
            self::Unknown => 'zinc',
        };
    }
}
