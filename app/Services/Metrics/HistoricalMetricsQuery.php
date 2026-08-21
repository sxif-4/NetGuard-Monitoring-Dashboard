<?php

namespace App\Services\Metrics;

use App\Models\Device;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HistoricalMetricsQuery
{
    private const FIELDS = ['cpu_pct', 'memory_pct', 'latency_ms', 'packet_loss_pct', 'bandwidth_pct'];

    /**
     * Range => [total lookback in seconds, bucket size in seconds]. Bucket
     * sizes are chosen so every range renders roughly 60-100 points
     * regardless of how far back it looks.
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private const RANGES = [
        '1h' => [3600, 60],
        '24h' => [86400, 900],
        '7d' => [604800, 7200],
        '30d' => [2592000, 28800],
    ];

    /**
     * Bucketed, averaged metric series for a device over the given range.
     *
     * Bucketing is done in PHP rather than via driver-specific SQL (Postgres'
     * `to_timestamp`/`extract` vs. SQLite's lack of them) — the test suite runs
     * against SQLite (phpunit.xml) while the app runs on Postgres, so this stays
     * portable across both without branching on the driver. Row volume per
     * device per range is small (a few hundred to ~1,000 rows at most), so
     * pulling them into PHP to bucket is cheap.
     *
     * @return array<int, array{
     *     bucket: string,
     *     cpu_pct: float|null,
     *     memory_pct: float|null,
     *     latency_ms: float|null,
     *     packet_loss_pct: float|null,
     *     bandwidth_pct: float|null,
     * }>
     */
    public function series(Device $device, string $range): array
    {
        [$lookbackSeconds, $bucketSeconds] = self::RANGES[$range] ?? self::RANGES['24h'];
        $since = now()->subSeconds($lookbackSeconds);

        $rows = DB::table('device_metrics')
            ->select(array_merge(['polled_at'], self::FIELDS))
            ->where('device_id', $device->id)
            ->where('polled_at', '>=', $since)
            ->orderBy('polled_at')
            ->get();

        $buckets = [];

        foreach ($rows as $row) {
            $bucketKey = intdiv(Carbon::parse($row->polled_at)->getTimestamp(), $bucketSeconds) * $bucketSeconds;
            $buckets[$bucketKey] ??= array_fill_keys(self::FIELDS, []);

            foreach (self::FIELDS as $field) {
                if ($row->{$field} !== null) {
                    $buckets[$bucketKey][$field][] = (float) $row->{$field};
                }
            }
        }

        ksort($buckets);

        $series = [];

        foreach ($buckets as $bucketKey => $values) {
            $series[] = [
                'bucket' => Carbon::createFromTimestamp($bucketKey)->toIso8601String(),
                ...array_map($this->average(...), $values),
            ];
        }

        return $series;
    }

    /**
     * @return array<int, string>
     */
    public function availableRanges(): array
    {
        return array_keys(self::RANGES);
    }

    /**
     * @param  array<int, float>  $values
     */
    private function average(array $values): ?float
    {
        return $values === [] ? null : round(array_sum($values) / count($values), 2);
    }
}
