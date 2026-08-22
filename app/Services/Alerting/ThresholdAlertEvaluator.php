<?php

namespace App\Services\Alerting;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMetric;
use App\Models\Threshold;
use DateTimeInterface;
use Illuminate\Support\Collection;

class ThresholdAlertEvaluator
{
    public function __construct(private readonly NotificationDispatcher $notifier) {}

    /**
     * metric key => [type slug, title, DeviceMetric accessor]
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const METRIC_ALERTS = [
        'cpu' => ['high_cpu', 'High CPU Usage', 'cpu_pct'],
        'memory' => ['high_memory', 'High Memory Usage', 'memory_pct'],
        'latency' => ['high_latency', 'High Latency', 'latency_ms'],
        'packet_loss' => ['packet_loss', 'Packet Loss', 'packet_loss_pct'],
        'bandwidth' => ['high_bandwidth', 'High Bandwidth Usage', 'bandwidth_pct'],
    ];

    /**
     * Evaluate one polled metric against the configured thresholds, creating or
     * updating the relevant operational Alert(s). Never resolves an alert —
     * that's left to a human, even after the underlying condition clears.
     *
     * @param  Collection<string, Threshold>  $thresholds  keyed by metric slug
     */
    public function evaluate(Device $device, DeviceMetric $metric, Collection $thresholds): void
    {
        if (! $metric->reachable) {
            $this->trigger(
                device: $device,
                type: 'device_offline',
                title: 'Device Offline',
                severity: AlertSeverity::Critical,
                description: 'Device did not respond to monitoring.',
                metadata: null,
                polledAt: $metric->polled_at,
            );

            return;
        }

        foreach (self::METRIC_ALERTS as $metricKey => [$type, $title, $accessor]) {
            $value = $metric->{$accessor};
            $threshold = $thresholds->get($metricKey);

            if ($value === null || $threshold === null) {
                continue;
            }

            $severity = $this->severityFor($value, $threshold);

            if ($severity === null) {
                continue;
            }

            $this->trigger(
                device: $device,
                type: $type,
                title: $title,
                severity: $severity,
                description: sprintf(
                    '%s reached %s%s, exceeding the %s threshold of %s%s.',
                    $threshold->metric->label(),
                    $value,
                    $threshold->unit,
                    $severity === AlertSeverity::Critical ? 'critical' : 'warning',
                    $severity === AlertSeverity::Critical ? $threshold->critical_threshold : $threshold->warning_threshold,
                    $threshold->unit,
                ),
                metadata: [
                    'value' => $value,
                    'threshold' => $severity === AlertSeverity::Critical ? $threshold->critical_threshold : $threshold->warning_threshold,
                    'unit' => $threshold->unit,
                ],
                polledAt: $metric->polled_at,
            );
        }
    }

    private function severityFor(float $value, Threshold $threshold): ?AlertSeverity
    {
        if ($threshold->critical_threshold !== null && $value >= $threshold->critical_threshold) {
            return AlertSeverity::Critical;
        }

        if ($value >= $threshold->warning_threshold) {
            return AlertSeverity::High;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function trigger(
        Device $device,
        string $type,
        string $title,
        AlertSeverity $severity,
        string $description,
        ?array $metadata,
        DateTimeInterface $polledAt,
    ): void {
        $existing = Alert::query()
            ->where('device_id', $device->id)
            ->where('type', $type)
            ->where('status', '!=', AlertStatus::Resolved)
            ->first();

        if ($existing !== null) {
            $existing->update([
                'severity' => $severity,
                'description' => $description,
                'metadata' => $metadata,
                'occurrences' => $existing->occurrences + 1,
                'last_detected_at' => $polledAt,
            ]);

            $this->notifier->dispatch($existing);

            return;
        }

        $alert = Alert::create([
            'device_id' => $device->id,
            'category' => AlertCategory::Operational,
            'type' => $type,
            'severity' => $severity,
            'status' => AlertStatus::Active,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'occurrences' => 1,
            'first_detected_at' => $polledAt,
            'last_detected_at' => $polledAt,
        ]);

        $this->notifier->dispatch($alert);
    }
}
