<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Models\DeviceMetric;
use App\Services\Metrics\HistoricalMetricsQuery;
use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Device')]
class Show extends Component
{
    public Device $device;

    #[Url]
    public string $range = '24h';

    /**
     * Chart id => DeviceMetric field, unit, color (fixed categorical order — never reassigned per chart).
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    private const CHARTS = [
        'cpu' => ['cpu_pct', 'CPU Usage', '%', '#2a78d6'],
        'memory' => ['memory_pct', 'Memory Usage', '%', '#eb6834'],
        'latency' => ['latency_ms', 'Latency', 'ms', '#1baf7a'],
        'packet-loss' => ['packet_loss_pct', 'Packet Loss', '%', '#eda100'],
        'bandwidth' => ['bandwidth_pct', 'Bandwidth Usage', '%', '#e87ba4'],
    ];

    public function mount(Device $device): void
    {
        $this->device = $device;
    }

    public function updatedRange(): void
    {
        foreach (self::CHARTS as $chartId => [$field]) {
            $this->dispatch("metrics-series-updated-{$chartId}", points: $this->pointsFor($field));
        }
    }

    /**
     * @return array<int, array{bucket: string, cpu_pct: float|null, memory_pct: float|null, latency_ms: float|null, packet_loss_pct: float|null, bandwidth_pct: float|null}>
     */
    #[Computed]
    public function series(): array
    {
        return app(HistoricalMetricsQuery::class)->series($this->device, $this->range);
    }

    #[Computed]
    public function latestMetric(): ?DeviceMetric
    {
        return DeviceMetric::where('device_id', $this->device->id)->latest('polled_at')->first();
    }

    #[Computed]
    public function uptimeLabel(): ?string
    {
        $seconds = $this->latestMetric()?->uptime_seconds;

        if ($seconds === null) {
            return null;
        }

        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);
    }

    /**
     * @return array<string, array<int, array{bucket: string, value: float|null}>>
     */
    #[Computed]
    public function chartPoints(): array
    {
        $points = [];

        foreach (self::CHARTS as $chartId => [$field]) {
            $points[$chartId] = $this->pointsFor($field);
        }

        return $points;
    }

    /**
     * @return array<int, array{bucket: string, value: float|null}>
     */
    private function pointsFor(string $field): array
    {
        return collect($this->series())
            ->map(fn (array $row) => [
                'bucket' => Carbon::parse($row['bucket'])->format('M j, H:i'),
                'value' => is_numeric($row[$field] ?? null) ? (float) $row[$field] : null,
            ])
            ->all();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public function charts(): array
    {
        return self::CHARTS;
    }

    public function render(): View
    {
        return view('livewire.devices.show');
    }
}
