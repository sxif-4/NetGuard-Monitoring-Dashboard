<?php

namespace Database\Seeders;

use App\Enums\ThresholdMetric;
use App\Models\Threshold;
use Illuminate\Database\Seeder;

class ThresholdSeeder extends Seeder
{
    /**
     * The default threshold values, per plan.md §8.
     *
     * @var array<string, array{warning: float, critical: float|null}>
     */
    private const DEFAULTS = [
        'cpu' => ['warning' => 75.0, 'critical' => 90.0],
        'memory' => ['warning' => 80.0, 'critical' => 95.0],
        'latency' => ['warning' => 100.0, 'critical' => 200.0],
        'packet_loss' => ['warning' => 5.0, 'critical' => null],
        'bandwidth' => ['warning' => 85.0, 'critical' => null],
    ];

    /**
     * Seed the application's default monitoring thresholds.
     */
    public function run(): void
    {
        foreach (self::DEFAULTS as $metric => $values) {
            $metric = ThresholdMetric::from($metric);

            Threshold::updateOrCreate(
                ['metric' => $metric->value],
                [
                    'label' => $metric->label(),
                    'warning_threshold' => $values['warning'],
                    'critical_threshold' => $values['critical'],
                    'unit' => $metric->unit(),
                ]
            );
        }
    }
}
