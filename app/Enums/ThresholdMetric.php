<?php

namespace App\Enums;

enum ThresholdMetric: string
{
    case Cpu = 'cpu';
    case Memory = 'memory';
    case Latency = 'latency';
    case PacketLoss = 'packet_loss';
    case Bandwidth = 'bandwidth';

    public function label(): string
    {
        return match ($this) {
            self::Cpu => 'CPU Utilization',
            self::Memory => 'Memory Utilization',
            self::Latency => 'Latency',
            self::PacketLoss => 'Packet Loss',
            self::Bandwidth => 'Bandwidth Utilization',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Latency => 'ms',
            default => '%',
        };
    }

    public function hasCriticalThreshold(): bool
    {
        return match ($this) {
            self::PacketLoss, self::Bandwidth => false,
            default => true,
        };
    }
}
