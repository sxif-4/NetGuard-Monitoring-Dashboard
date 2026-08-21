<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property Device $device
 * @property bool $reachable
 * @property float|null $latency_ms
 * @property float|null $packet_loss_pct
 * @property float|null $cpu_pct
 * @property float|null $memory_pct
 * @property float|null $bandwidth_pct
 * @property int|null $bandwidth_in_bps
 * @property int|null $bandwidth_out_bps
 * @property int|null $uptime_seconds
 * @property string|null $error
 * @property Carbon $polled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['device_id', 'reachable', 'latency_ms', 'packet_loss_pct', 'cpu_pct', 'memory_pct', 'bandwidth_pct', 'bandwidth_in_bps', 'bandwidth_out_bps', 'uptime_seconds', 'error', 'polled_at'])]
class DeviceMetric extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reachable' => 'boolean',
            'latency_ms' => 'float',
            'packet_loss_pct' => 'float',
            'cpu_pct' => 'float',
            'memory_pct' => 'float',
            'bandwidth_pct' => 'float',
            'bandwidth_in_bps' => 'integer',
            'bandwidth_out_bps' => 'integer',
            'uptime_seconds' => 'integer',
            'polled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
