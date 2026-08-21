<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Detected = 'detected';
    case Active = 'active';
    case Acknowledged = 'acknowledged';
    case Investigating = 'investigating';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Detected => 'Detected',
            self::Active => 'Active',
            self::Acknowledged => 'Acknowledged',
            self::Investigating => 'Investigating',
            self::Resolved => 'Resolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Detected, self::Active => 'red',
            self::Acknowledged => 'amber',
            self::Investigating => 'blue',
            self::Resolved => 'green',
        };
    }
}
