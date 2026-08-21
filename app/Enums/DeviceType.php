<?php

namespace App\Enums;

enum DeviceType: string
{
    case Router = 'router';
    case Switch = 'switch';
    case Firewall = 'firewall';
    case Server = 'server';
    case AccessPoint = 'access_point';
    case Workstation = 'workstation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Router => 'Router',
            self::Switch => 'Switch',
            self::Firewall => 'Firewall',
            self::Server => 'Server',
            self::AccessPoint => 'Access Point',
            self::Workstation => 'Workstation',
            self::Other => 'Other',
        };
    }
}
