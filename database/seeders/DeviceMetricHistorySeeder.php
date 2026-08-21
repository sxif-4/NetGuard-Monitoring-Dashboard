<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Enums\MonitoringMethod;
use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Database\Seeder;

class DeviceMetricHistorySeeder extends Seeder
{
    private const BATCH_SIZE = 250;

    /**
     * Backfill ~30 days of plausible-looking device_metrics history for a
     * handful of devices, so every historical chart range (1h/24h/7d/30d)
     * shows a real-looking trend immediately instead of being empty.
     */
    public function run(): void
    {
        $targets = Device::query()
            ->whereIn('name', ['localhost', 'cloudflare-dns', 'google-dns', 'unreachable-test'])
            ->get()
            ->merge(Device::where('status', DeviceStatus::Offline)->where('monitoring_enabled', false)->limit(1)->get())
            ->merge(Device::where('status', DeviceStatus::Warning)->limit(2)->get());

        foreach ($targets as $device) {
            $this->backfill($device);
        }
    }

    private function backfill(Device $device): void
    {
        $alwaysUnreachable = $device->name === 'unreachable-test';
        $flaky = $device->status === DeviceStatus::Offline && ! $alwaysUnreachable;
        $hasSnmp = $device->monitoring_method === MonitoringMethod::Snmp;

        $cpuBaseline = fake()->numberBetween(5, 35);
        $memoryBaseline = fake()->numberBetween(20, 55);
        $latencyBaseline = $device->ip_address === '127.0.0.1' ? 0.5 : fake()->numberBetween(10, 120);

        $now = now();
        $cutoff = $now->copy()->subDays(30)->getTimestamp();
        $uptimeSeconds = 0;

        $batch = [];
        $timestamp = $cutoff;

        while ($timestamp <= $now->getTimestamp()) {
            $reachable = ! $alwaysUnreachable && ! ($flaky && fake()->boolean(30));
            $spike = $reachable && fake()->boolean(5);

            $uptimeSeconds = $reachable ? $uptimeSeconds + 1800 : 0;

            $polledAt = date('Y-m-d H:i:s', $timestamp);

            $batch[] = [
                'device_id' => $device->id,
                'reachable' => $reachable,
                'latency_ms' => $reachable ? round($latencyBaseline + fake()->randomFloat(2, -1, 5) + ($spike ? fake()->numberBetween(20, 80) : 0), 2) : null,
                'packet_loss_pct' => $reachable ? ($spike ? fake()->randomFloat(2, 5, 20) : 0.0) : 100.0,
                'cpu_pct' => $reachable && $hasSnmp ? $this->clampPct($cpuBaseline + fake()->numberBetween(-8, 8) + ($spike ? fake()->numberBetween(30, 60) : 0)) : null,
                'memory_pct' => $reachable && $hasSnmp ? $this->clampPct($memoryBaseline + fake()->numberBetween(-5, 5)) : null,
                'bandwidth_pct' => $reachable && $hasSnmp ? $this->clampPct(fake()->numberBetween(2, 20) + ($spike ? fake()->numberBetween(40, 70) : 0)) : null,
                'bandwidth_in_bps' => $reachable && $hasSnmp ? fake()->numberBetween(10_000, 5_000_000) : null,
                'bandwidth_out_bps' => $reachable && $hasSnmp ? fake()->numberBetween(5_000, 2_000_000) : null,
                'uptime_seconds' => $reachable ? $uptimeSeconds : null,
                'error' => $reachable ? null : 'timeout',
                'polled_at' => $polledAt,
                'created_at' => $polledAt,
                'updated_at' => $polledAt,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                DeviceMetric::insert($batch);
                $batch = [];
            }

            // 30-60 minute step, in seconds — plain integer arithmetic, no Carbon/object churn per iteration.
            $timestamp += fake()->numberBetween(30, 60) * 60;
        }

        if ($batch !== []) {
            DeviceMetric::insert($batch);
        }
    }

    private function clampPct(int $value): float
    {
        return (float) max(0, min(100, $value));
    }
}
