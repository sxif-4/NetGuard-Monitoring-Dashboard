<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\MonitoringMethod;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $method = fake()->randomElement(MonitoringMethod::cases());

        return [
            'name' => fake()->unique()->domainWord().'-'.fake()->numberBetween(1, 99),
            'type' => fake()->randomElement(DeviceType::cases()),
            'ip_address' => fake()->unique()->ipv4(),
            'monitoring_method' => $method,
            'monitoring_port' => $method === MonitoringMethod::Snmp ? 161 : null,
            'monitoring_enabled' => true,
            'status' => DeviceStatus::Online,
            'location' => fake()->city(),
            'last_seen_at' => fake()->dateTimeBetween('-5 minutes', 'now'),
        ];
    }

    /**
     * Indicate that the device is in a warning state.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Warning,
            'last_seen_at' => fake()->dateTimeBetween('-15 minutes', '-5 minutes'),
        ]);
    }

    /**
     * Indicate that the device is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Offline,
            'last_seen_at' => fake()->dateTimeBetween('-1 day', '-1 hour'),
        ]);
    }

    /**
     * Indicate that monitoring is disabled for this device.
     */
    public function monitoringDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'monitoring_enabled' => false,
            'status' => DeviceStatus::Unknown,
            'last_seen_at' => null,
        ]);
    }
}
