<?php

namespace App\Enums;

enum SecurityAlertType: string
{
    case PortScan = 'port_scan';
    case DosActivity = 'dos_activity';
    case SuspiciousIp = 'suspicious_ip';
    case TrafficAnomaly = 'traffic_anomaly';
    case UnusualConnection = 'unusual_connection';
    case DataExfiltration = 'data_exfiltration';

    /**
     * The single source of truth for slug -> alert title, per plan.md §7.2.
     * database/factories/AlertFactory.php reuses this rather than keeping
     * its own copy, so seeded demo alerts and real ML-generated ones always
     * agree on wording.
     */
    public function label(): string
    {
        return match ($this) {
            self::PortScan => 'Port Scan Detected',
            self::DosActivity => 'DoS Activity',
            self::SuspiciousIp => 'Suspicious IP Activity',
            self::TrafficAnomaly => 'Traffic Anomaly',
            self::UnusualConnection => 'Unusual Connection Pattern',
            self::DataExfiltration => 'Possible Data Exfiltration',
        };
    }
}
