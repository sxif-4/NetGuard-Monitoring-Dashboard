<?php

namespace App\Livewire;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\SecurityAlertType;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Overview')]
class Dashboard extends Component
{
    /**
     * @return array<string, int>
     */
    #[Computed]
    public function deviceCounts(): array
    {
        $counts = Device::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total' => (int) $counts->sum(),
            'online' => (int) ($counts[DeviceStatus::Online->value] ?? 0),
            'warning' => (int) ($counts[DeviceStatus::Warning->value] ?? 0),
            'offline' => (int) ($counts[DeviceStatus::Offline->value] ?? 0),
        ];
    }

    #[Computed]
    public function networkHealth(): string
    {
        $hasOfflineDevice = Device::query()
            ->where('monitoring_enabled', true)
            ->where('status', DeviceStatus::Offline)
            ->exists();

        $hasCriticalAlert = Alert::query()
            ->where('status', '!=', AlertStatus::Resolved)
            ->where('severity', AlertSeverity::Critical)
            ->exists();

        if ($hasOfflineDevice || $hasCriticalAlert) {
            return 'critical';
        }

        $hasWarningDevice = Device::query()
            ->where('monitoring_enabled', true)
            ->where('status', DeviceStatus::Warning)
            ->exists();

        $hasElevatedAlert = Alert::query()
            ->where('status', '!=', AlertStatus::Resolved)
            ->whereIn('severity', [AlertSeverity::High, AlertSeverity::Medium])
            ->exists();

        if ($hasWarningDevice || $hasElevatedAlert) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * @return array{total: int, critical: int}
     */
    #[Computed]
    public function activeAlertsSummary(): array
    {
        $counts = Alert::query()
            ->where('status', '!=', AlertStatus::Resolved)
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        return [
            'total' => (int) $counts->sum(),
            'critical' => (int) ($counts[AlertSeverity::Critical->value] ?? 0),
        ];
    }

    /**
     * Aggregate bandwidth/latency/packet-loss across every device's most
     * recent poll — the "at a glance" numbers for the top stat row.
     *
     * @return array{bandwidth_bps: int, avg_latency_ms: float|null, avg_packet_loss_pct: float|null}
     */
    #[Computed]
    public function networkTotals(): array
    {
        $latest = $this->latestMetricsByDevice();

        $bandwidthBps = (int) $latest->sum(
            fn (DeviceMetric $metric) => ($metric->bandwidth_in_bps ?? 0) + ($metric->bandwidth_out_bps ?? 0)
        );
        $latencies = $latest->pluck('latency_ms')->filter(fn ($value) => $value !== null);
        $packetLoss = $latest->pluck('packet_loss_pct')->filter(fn ($value) => $value !== null);

        return [
            'bandwidth_bps' => $bandwidthBps,
            'avg_latency_ms' => $latencies->isEmpty() ? null : round($latencies->avg(), 1),
            'avg_packet_loss_pct' => $packetLoss->isEmpty() ? null : round($packetLoss->avg(), 2),
        ];
    }

    /**
     * A quick-glance device health table: devices with real polling
     * history, worst status first, each with a short sparkline of its
     * last few polls for CPU/memory/bandwidth.
     *
     * @return array<int, array{device: Device, cpu_pct: float|null, memory_pct: float|null, bandwidth_mbps: float|null, cpu_series: array<int, float>, memory_series: array<int, float>, bandwidth_series: array<int, float>}>
     */
    #[Computed]
    public function deviceHealthRows(): array
    {
        $latest = $this->latestMetricsByDevice();

        // Devices whose *latest* poll actually carries a metric value (SNMP
        // CPU/memory/bandwidth, or at minimum a real latency reading) surface
        // first -- an unreachable/ICMP-only device's latest row is often all
        // null, and would otherwise crowd out the devices worth looking at.
        $hasSignal = $latest->filter(
            fn (DeviceMetric $metric) => $metric->cpu_pct !== null
                || $metric->memory_pct !== null
                || $metric->bandwidth_in_bps !== null
                || $metric->bandwidth_out_bps !== null
                || $metric->latency_ms !== null
        )->keys();

        $statusOrder = "case status when 'offline' then 0 when 'warning' then 1 when 'unknown' then 2 else 3 end";

        $devices = Device::query()
            ->whereHas('metrics')
            ->whereIn('id', $hasSignal)
            ->orderByRaw($statusOrder)
            ->orderBy('name')
            ->limit(8)
            ->get();

        if ($devices->count() < 8) {
            $filler = Device::query()
                ->whereHas('metrics')
                ->whereNotIn('id', $devices->pluck('id'))
                ->orderByRaw($statusOrder)
                ->orderBy('name')
                ->limit(8 - $devices->count())
                ->get();

            $devices = $devices->concat($filler);
        }

        return $devices->map(function (Device $device) {
            $history = DeviceMetric::query()
                ->where('device_id', $device->id)
                ->orderByDesc('polled_at')
                ->limit(12)
                ->get()
                ->reverse()
                ->values();

            $latestForDevice = $history->last();
            $hasBandwidth = $latestForDevice !== null
                && ($latestForDevice->bandwidth_in_bps !== null || $latestForDevice->bandwidth_out_bps !== null);

            return [
                'device' => $device,
                'cpu_pct' => $latestForDevice?->cpu_pct,
                'memory_pct' => $latestForDevice?->memory_pct,
                'bandwidth_mbps' => $hasBandwidth ? round(
                    (($latestForDevice->bandwidth_in_bps ?? 0) + ($latestForDevice->bandwidth_out_bps ?? 0)) / 1_000_000,
                    1
                ) : null,
                'cpu_series' => $history->pluck('cpu_pct')->filter(fn ($value) => $value !== null)->values()->all(),
                'memory_series' => $history->pluck('memory_pct')->filter(fn ($value) => $value !== null)->values()->all(),
                'bandwidth_series' => $history
                    ->filter(fn (DeviceMetric $metric) => $metric->bandwidth_in_bps !== null || $metric->bandwidth_out_bps !== null)
                    ->map(fn (DeviceMetric $metric) => (($metric->bandwidth_in_bps ?? 0) + ($metric->bandwidth_out_bps ?? 0)) / 1_000_000)
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    /**
     * @return Collection<int, DeviceMetric> keyed by device_id
     */
    private function latestMetricsByDevice(): Collection
    {
        $latestIds = DeviceMetric::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('device_id')
            ->pluck('id');

        return DeviceMetric::query()->whereIn('id', $latestIds)->get()->keyBy('device_id');
    }

    /**
     * @return Collection<int, Alert>
     */
    #[Computed]
    public function recentAlerts(): Collection
    {
        return Alert::query()
            ->with('device')
            ->where('status', '!=', AlertStatus::Resolved)
            ->orderByDesc('last_detected_at')
            ->limit(5)
            ->get();
    }

    /**
     * Security Summary widget (plan.md §5.4) — counts of unresolved
     * security-category alerts, by type.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function securitySummary(): array
    {
        $counts = Alert::query()
            ->where('category', AlertCategory::Security)
            ->where('status', '!=', AlertStatus::Resolved)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $highRiskEvents = Alert::query()
            ->where('category', AlertCategory::Security)
            ->where('status', '!=', AlertStatus::Resolved)
            ->whereIn('severity', [AlertSeverity::Critical, AlertSeverity::High])
            ->count();

        return [
            'anomalies_detected' => (int) $counts->sum(),
            'port_scan_attempts' => (int) ($counts[SecurityAlertType::PortScan->value] ?? 0),
            'dos_activity' => (int) ($counts[SecurityAlertType::DosActivity->value] ?? 0),
            'suspicious_behaviour' => (int) (
                ($counts[SecurityAlertType::SuspiciousIp->value] ?? 0)
                + ($counts[SecurityAlertType::UnusualConnection->value] ?? 0)
                + ($counts[SecurityAlertType::TrafficAnomaly->value] ?? 0)
            ),
            'high_risk_events' => $highRiskEvents,
        ];
    }

    /**
     * Daily alert counts over the last 30 days, for the Overview trend chart.
     *
     * Bucketed in PHP rather than via `date_trunc` (Postgres-only — the test
     * suite runs on SQLite per phpunit.xml) to stay portable across both.
     *
     * @return array<int, array{bucket: string, value: int}>
     */
    #[Computed]
    public function alertCountSeries(): array
    {
        $since = now()->subDays(30)->startOfDay();

        $counts = Alert::query()
            ->where('first_detected_at', '>=', $since)
            ->pluck('first_detected_at')
            ->countBy(fn ($timestamp) => $timestamp->format('Y-m-d'));

        $points = [];
        $endTimestamp = now()->getTimestamp();

        for ($timestamp = $since->getTimestamp(); $timestamp <= $endTimestamp; $timestamp += 86400) {
            $points[] = [
                'bucket' => date('M j', $timestamp),
                'value' => (int) ($counts[date('Y-m-d', $timestamp)] ?? 0),
            ];
        }

        return $points;
    }

    public function render(): View
    {
        return view('livewire.dashboard')
            ->layout(auth()->user()->isAdmin() ? 'layouts.app' : 'layouts.restricted');
    }
}
