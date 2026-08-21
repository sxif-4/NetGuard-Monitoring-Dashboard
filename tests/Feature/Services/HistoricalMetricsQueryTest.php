<?php

namespace Tests\Feature\Services;

use App\Models\Device;
use App\Models\DeviceMetric;
use App\Services\Metrics\HistoricalMetricsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalMetricsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_averages_values_within_the_same_bucket(): void
    {
        $device = Device::factory()->create();

        // Same timestamp for both rows — guarantees the same bucket deterministically
        // (a real time gap risks flaking across a bucket boundary depending on alignment).
        $polledAt = now()->subMinutes(5);

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 20.0,
            'polled_at' => $polledAt,
        ]);

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 40.0,
            'polled_at' => $polledAt,
        ]);

        $series = app(HistoricalMetricsQuery::class)->series($device, '1h');

        $this->assertCount(1, $series);
        $this->assertSame(30.0, $series[0]['cpu_pct']);
    }

    public function test_it_excludes_rows_outside_the_requested_range(): void
    {
        $device = Device::factory()->create();

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 50.0,
            'polled_at' => now()->subMinutes(5),
        ]);

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 90.0,
            'polled_at' => now()->subDays(2),
        ]);

        $series = app(HistoricalMetricsQuery::class)->series($device, '1h');

        $this->assertCount(1, $series);
        $this->assertSame(50.0, $series[0]['cpu_pct']);
    }

    public function test_null_metric_values_are_excluded_from_the_average_not_treated_as_zero(): void
    {
        $device = Device::factory()->create();

        $polledAt = now()->subMinutes(5);

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 40.0,
            'polled_at' => $polledAt,
        ]);

        DeviceMetric::factory()->unreachable()->create([
            'device_id' => $device->id,
            'polled_at' => $polledAt,
        ]);

        $series = app(HistoricalMetricsQuery::class)->series($device, '1h');

        $this->assertCount(1, $series);
        $this->assertSame(40.0, $series[0]['cpu_pct']);
    }

    public function test_a_device_with_no_metrics_returns_an_empty_series(): void
    {
        $device = Device::factory()->create();

        $series = app(HistoricalMetricsQuery::class)->series($device, '24h');

        $this->assertSame([], $series);
    }

    public function test_longer_ranges_use_wider_buckets(): void
    {
        $device = Device::factory()->create();

        // Two points a couple of minutes apart land in the same 8h bucket at 30d
        // granularity (kept tight to avoid flaking across an 8h bucket boundary).
        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 10.0,
            'polled_at' => now()->subMinutes(2),
        ]);

        DeviceMetric::factory()->create([
            'device_id' => $device->id,
            'cpu_pct' => 30.0,
            'polled_at' => now(),
        ]);

        $series = app(HistoricalMetricsQuery::class)->series($device, '30d');

        $this->assertCount(1, $series);
        $this->assertSame(20.0, $series[0]['cpu_pct']);
    }

    public function test_available_ranges_lists_all_four_ranges(): void
    {
        $ranges = app(HistoricalMetricsQuery::class)->availableRanges();

        $this->assertSame(['1h', '24h', '7d', '30d'], $ranges);
    }
}
