<?php

namespace App\Models;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $device_id
 * @property Device|null $device
 * @property AlertCategory $category
 * @property string $type
 * @property AlertSeverity $severity
 * @property AlertStatus $status
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property int $occurrences
 * @property Carbon $first_detected_at
 * @property Carbon $last_detected_at
 * @property int|null $acknowledged_by
 * @property Carbon|null $acknowledged_at
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['device_id', 'category', 'type', 'severity', 'status', 'title', 'description', 'metadata', 'occurrences', 'first_detected_at', 'last_detected_at', 'acknowledged_by', 'acknowledged_at', 'resolved_by', 'resolved_at'])]
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AlertCategory::class,
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'metadata' => 'array',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
