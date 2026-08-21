<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceMetric>
 */
class DeviceMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'reachable' => true,
            'latency_ms' => fake()->randomFloat(2, 0.2, 150),
            'packet_loss_pct' => 0.0,
            'cpu_pct' => fake()->numberBetween(5, 60),
            'memory_pct' => fake()->numberBetween(10, 70),
            'bandwidth_pct' => fake()->numberBetween(1, 40),
            'bandwidth_in_bps' => fake()->numberBetween(10_000, 5_000_000),
            'bandwidth_out_bps' => fake()->numberBetween(5_000, 2_000_000),
            'uptime_seconds' => fake()->numberBetween(60, 2_592_000),
            'error' => null,
            'polled_at' => now(),
        ];
    }

    /**
     * Indicate that the device was unreachable for this poll.
     */
    public function unreachable(): static
    {
        return $this->state(fn (array $attributes) => [
            'reachable' => false,
            'latency_ms' => null,
            'packet_loss_pct' => 100.0,
            'cpu_pct' => null,
            'memory_pct' => null,
            'bandwidth_pct' => null,
            'bandwidth_in_bps' => null,
            'bandwidth_out_bps' => null,
            'uptime_seconds' => null,
            'error' => 'timeout',
        ]);
    }
}
