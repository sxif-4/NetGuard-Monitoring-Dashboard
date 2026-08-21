<?php

namespace App\Concerns;

use App\Enums\DeviceType;
use App\Enums\MonitoringMethod;
use App\Models\Device;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait DeviceValidationRules
{
    /**
     * Get the validation rules used to validate devices.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function deviceRules(?int $deviceId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(DeviceType::class)],
            'ipAddress' => [
                'required',
                'ip',
                $deviceId === null
                    ? Rule::unique(Device::class, 'ip_address')
                    : Rule::unique(Device::class, 'ip_address')->ignore($deviceId),
            ],
            'monitoringMethod' => ['required', Rule::enum(MonitoringMethod::class)],
            'monitoringPort' => ['nullable', 'integer', 'between:1,65535'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
