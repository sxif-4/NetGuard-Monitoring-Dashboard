<?php

namespace App\Services\Alerting;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\SecurityAlertType;
use App\Models\Alert;
use App\Models\Device;
use Illuminate\Support\Carbon;

class SecurityAlertEvaluator
{
    /**
     * anomaly_score cutoffs -> AlertSeverity. The daemon already filters out
     * anything below its own NETGUARD_ML_RISK_LOW_THRESHOLD before sending an
     * event at all, but Laravel is deliberately the single place that maps a
     * score to a *severity* -- mirrors ThresholdAlertEvaluator's own split of
     * responsibility (Python observes/scores, Laravel decides what it means).
     */
    private const SCORE_CRITICAL = 85.0;

    private const SCORE_HIGH = 70.0;

    private const SCORE_MEDIUM = 50.0;

    private const SCORE_LOW = 30.0;

    /**
     * Evaluate one ML-flagged security event, creating or updating the
     * relevant security Alert. Dedup is keyed on type + source IP (unlike
     * ThresholdAlertEvaluator's device_id + type, since these events are
     * IP-based and don't always resolve to a monitored Device row) — never
     * resolves an existing alert, same lifecycle rule as operational alerts.
     *
     * @param  array<string, mixed>  $event  one validated entry from
     *                                       StoreSecurityEventsRequest
     */
    public function evaluate(array $event): void
    {
        $score = (float) $event['anomaly_score'];
        $severity = $this->severityFor($score);

        if ($severity === null) {
            return;
        }

        $type = SecurityAlertType::from($event['type']);
        $sourceIp = $event['source_ip'];
        $destinationIp = $event['destination_ip'] ?? null;
        $detectedAt = Carbon::parse($event['detected_at']);

        $device = Device::query()
            ->where('ip_address', $sourceIp)
            ->when($destinationIp !== null, fn ($query) => $query->orWhere('ip_address', $destinationIp))
            ->first();

        $metadata = [
            'source_ip' => $sourceIp,
            'destination_ip' => $destinationIp,
            'source_port' => $event['source_port'] ?? null,
            'destination_port' => $event['destination_port'] ?? null,
            'protocol' => $event['protocol'],
            'model' => $event['model'],
            'anomaly_score' => $score,
            'possible_activity' => $event['possible_activity'],
        ];

        $existing = Alert::query()
            ->where('type', $type->value)
            ->where('metadata->source_ip', $sourceIp)
            ->where('status', '!=', AlertStatus::Resolved)
            ->first();

        if ($existing !== null) {
            $existing->update([
                'severity' => $severity,
                'metadata' => $metadata,
                'occurrences' => $existing->occurrences + 1,
                'last_detected_at' => $detectedAt,
            ]);

            return;
        }

        Alert::create([
            'device_id' => $device?->id,
            'category' => AlertCategory::Security,
            'type' => $type->value,
            'severity' => $severity,
            'status' => AlertStatus::Active,
            'title' => $type->label(),
            'description' => sprintf(
                '%s detected from %s (%s, anomaly score %.0f/100).',
                $event['possible_activity'],
                $sourceIp,
                $event['model'] === 'random_forest' ? 'Random Forest' : 'Isolation Forest',
                $score,
            ),
            'metadata' => $metadata,
            'occurrences' => 1,
            'first_detected_at' => $detectedAt,
            'last_detected_at' => $detectedAt,
        ]);
    }

    private function severityFor(float $score): ?AlertSeverity
    {
        return match (true) {
            $score >= self::SCORE_CRITICAL => AlertSeverity::Critical,
            $score >= self::SCORE_HIGH => AlertSeverity::High,
            $score >= self::SCORE_MEDIUM => AlertSeverity::Medium,
            $score >= self::SCORE_LOW => AlertSeverity::Low,
            default => null,
        };
    }
}
