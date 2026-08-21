<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\MonitoringMethod;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property DeviceType $type
 * @property string $ip_address
 * @property MonitoringMethod $monitoring_method
 * @property int|null $monitoring_port
 * @property string|null $snmp_community
 * @property string|null $snmp_version
 * @property int|null $snmp_port
 * @property bool $monitoring_enabled
 * @property DeviceStatus $status
 * @property string|null $location
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'type', 'ip_address', 'monitoring_method', 'monitoring_port', 'snmp_community', 'snmp_version', 'snmp_port', 'monitoring_enabled', 'status', 'location', 'last_seen_at'])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeviceType::class,
            'monitoring_method' => MonitoringMethod::class,
            'monitoring_enabled' => 'boolean',
            'status' => DeviceStatus::class,
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * @return HasMany<DeviceMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(DeviceMetric::class);
    }
}
