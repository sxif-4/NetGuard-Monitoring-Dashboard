<?php

namespace App\Enums;

enum MonitoringMethod: string
{
    case Icmp = 'icmp';
    case Snmp = 'snmp';
    case Http = 'http';
    case Tcp = 'tcp';

    public function label(): string
    {
        return match ($this) {
            self::Icmp => 'ICMP (Ping)',
            self::Snmp => 'SNMP',
            self::Http => 'HTTP',
            self::Tcp => 'TCP Port Check',
        };
    }
}
