<?php

namespace App\Enums;

enum AlertCategory: string
{
    case Operational = 'operational';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operational',
            self::Security => 'Security',
        };
    }
}
