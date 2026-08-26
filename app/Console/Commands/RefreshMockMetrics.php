<?php

namespace App\Console\Commands;

use App\Enums\DeviceStatus;
use App\Enums\MonitoringMethod;
use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Console\Command;

class RefreshMockMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:refresh-mock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Append a freshly jittered metric reading for every non-live (monitoring_enabled=false) device, so demo devices keep looking active between reseeds. Intentionally bypasses ThresholdAlertEvaluator -- this is cosmetic drift, not real monitoring.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $devices = Device::query()->where('monitoring_enabled', false)->get();

        $refreshed = 0;

        foreach ($devices as $device) {
            if ($this->refresh($device)) {
                $refreshed++;
            }
        }

        $this->info("Refreshed mock metrics for {$refreshed} device(s).");

        return self::SUCCESS;
    }

    private function refresh(Device $device): bool
    {
        $last = DeviceMetric::query()
            ->where('device_id', $device->id)
            ->orderByDesc('polled_at')
            ->first();

        if ($last === null) {
            return false;
        }

        $alwaysUnreachable = $device->name === 'unreachable-test';
        $hasSnmp = $device->monitoring_method === MonitoringMethod::Snmp;
        $flaky = $device->status === DeviceStatus::Offline;

        $reachable = ! $alwaysUnreachable && fake()->boolean($flaky ? 40 : 96);
        $spike = $reachable && fake()->boolean(5);

        DeviceMetric::create([
            'device_id' => $device->id,
            'reachable' => $reachable,
            'latency_ms' => $reachable
                ? $this->jitter($last->latency_ms ?? 20.0, 6.0, 0.2, 400.0) + ($spike ? fake()->numberBetween(20, 80) : 0)
                : null,
            'packet_loss_pct' => ! $reachable ? 100.0 : ($spike ? fake()->randomFloat(2, 5, 20) : 0.0),
            'cpu_pct' => $reachable && $hasSnmp ? $this->jitter($last->cpu_pct ?? 20.0, 6.0, 0.0, 100.0) : null,
            'memory_pct' => $reachable && $hasSnmp ? $this->jitter($last->memory_pct ?? 40.0, 4.0, 0.0, 100.0) : null,
            'bandwidth_pct' => $reachable && $hasSnmp ? $this->jitter($last->bandwidth_pct ?? 15.0, 8.0, 0.0, 100.0) : null,
            'bandwidth_in_bps' => $reachable && $hasSnmp
                ? $this->jitterInt($last->bandwidth_in_bps ?? 500_000, 0.3, 10_000, 8_000_000)
                : null,
            'bandwidth_out_bps' => $reachable && $hasSnmp
                ? $this->jitterInt($last->bandwidth_out_bps ?? 200_000, 0.3, 5_000, 4_000_000)
                : null,
            'uptime_seconds' => $reachable ? ($last->uptime_seconds ?? 0) + 300 : null,
            'error' => $reachable ? null : 'timeout',
            'polled_at' => now(),
        ]);

        return true;
    }

    private function jitter(float $value, float $delta, float $min, float $max): float
    {
        $next = $value + fake()->randomFloat(2, -$delta, $delta);

        return round(max($min, min($max, $next)), 2);
    }

    private function jitterInt(int $value, float $pct, int $min, int $max): int
    {
        $next = (int) round($value + $value * fake()->randomFloat(3, -$pct, $pct));

        return max($min, min($max, $next));
    }
}
