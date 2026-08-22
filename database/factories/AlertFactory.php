<?php

namespace Database\Factories;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\SecurityAlertType;
use App\Models\Alert;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Alert type slug => display title, per plan.md §7.1/§7.2.
     *
     * @var array<string, string>
     */
    private const OPERATIONAL_TYPES = [
        'device_offline' => 'Device Offline',
        'high_cpu' => 'High CPU Usage',
        'high_memory' => 'High Memory Usage',
        'high_latency' => 'High Latency',
        'packet_loss' => 'Packet Loss',
        'interface_down' => 'Interface Down',
        'high_bandwidth' => 'High Bandwidth Usage',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(AlertCategory::cases());

        if ($category === AlertCategory::Operational) {
            $type = fake()->randomElement(array_keys(self::OPERATIONAL_TYPES));
            $title = self::OPERATIONAL_TYPES[$type];
        } else {
            $securityType = fake()->randomElement(SecurityAlertType::cases());
            $type = $securityType->value;
            $title = $securityType->label();
        }

        $firstDetected = fake()->dateTimeBetween('-7 days', '-1 hour');

        return [
            'device_id' => $category === AlertCategory::Operational ? Device::factory() : null,
            'category' => $category,
            'type' => $type,
            'severity' => fake()->randomElement([
                AlertSeverity::Low, AlertSeverity::Low,
                AlertSeverity::Medium, AlertSeverity::Medium,
                AlertSeverity::High,
                AlertSeverity::Critical,
                AlertSeverity::Informational,
            ]),
            'status' => fake()->randomElement(AlertStatus::cases()),
            'title' => $title,
            'description' => fake()->sentence(12),
            'metadata' => null,
            'occurrences' => fake()->numberBetween(1, 5),
            'first_detected_at' => $firstDetected,
            'last_detected_at' => fake()->dateTimeBetween($firstDetected, 'now'),
        ];
    }

    /**
     * Indicate that the alert has been acknowledged.
     */
    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AlertStatus::Acknowledged,
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => fake()->dateTimeBetween($attributes['first_detected_at'] ?? '-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the alert is under investigation.
     */
    public function investigating(): static
    {
        return $this->acknowledged()->state(fn (array $attributes) => [
            'status' => AlertStatus::Investigating,
        ]);
    }

    /**
     * Indicate that the alert has been resolved.
     */
    public function resolved(): static
    {
        return $this->acknowledged()->state(fn (array $attributes) => [
            'status' => AlertStatus::Resolved,
            'resolved_by' => User::factory(),
            'resolved_at' => fake()->dateTimeBetween($attributes['acknowledged_at'] ?? '-1 day', 'now'),
        ]);
    }
}
