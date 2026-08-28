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

    private const DEVICE_OFFLINE_TYPE = 'device_offline';

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
     * updating the relevant operational Alert(s). Threshold breaches (high
     * CPU, latency, etc.) are never auto-resolved — that's left to a human,
     * even after the underlying condition clears. device_offline is the one
     * exception: reachability is a clean binary the daemon itself observes
     * directly each cycle, so a device answering again auto-resolves its own
     * offline alert and fires an "Up" recovery notification.
     *
     * @param  Collection<string, Threshold>  $thresholds  keyed by metric slug
     */
    public function evaluate(Device $device, DeviceMetric $metric, Collection $thresholds): void
    {
        if (! $metric->reachable) {
            $this->trigger(
                device: $device,
                type: self::DEVICE_OFFLINE_TYPE,
                title: 'Device Offline',
                severity: AlertSeverity::Critical,
                description: 'Device did not respond to monitoring.',
                metadata: null,
                polledAt: $metric->polled_at,
            );

            return;
        }

        $this->recoverIfWasOffline($device, $metric->polled_at);

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

    private function recoverIfWasOffline(Device $device, DateTimeInterface $polledAt): void
    {
        $alert = Alert::query()
            ->where('device_id', $device->id)
            ->where('type', self::DEVICE_OFFLINE_TYPE)
            ->where('status', '!=', AlertStatus::Resolved)
            ->first();

        if ($alert === null) {
            return;
        }

        $alert->update([
            'status' => AlertStatus::Resolved,
            'resolved_at' => $polledAt,
        ]);

        $this->notifier->notifyRecovery($alert);
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
