<?php

namespace App\Models;

use App\Enums\ThresholdMetric;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ThresholdMetric $metric
 * @property string $label
 * @property float $warning_threshold
 * @property float|null $critical_threshold
 * @property string $unit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['metric', 'label', 'warning_threshold', 'critical_threshold', 'unit'])]
class Threshold extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metric' => ThresholdMetric::class,
            'warning_threshold' => 'float',
            'critical_threshold' => 'float',
        ];
    }
}
