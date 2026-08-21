<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceMetricsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'metrics' => ['required', 'array', 'min:1'],
            'metrics.*.device_id' => ['required', 'integer', 'exists:devices,id'],
            'metrics.*.reachable' => ['required', 'boolean'],
            'metrics.*.polled_at' => ['required', 'date'],
            'metrics.*.latency_ms' => ['nullable', 'numeric', 'min:0'],
            'metrics.*.packet_loss_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metrics.*.cpu_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metrics.*.memory_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metrics.*.bandwidth_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metrics.*.bandwidth_in_bps' => ['nullable', 'integer', 'min:0'],
            'metrics.*.bandwidth_out_bps' => ['nullable', 'integer', 'min:0'],
            'metrics.*.uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'metrics.*.error' => ['nullable', 'string'],
        ];
    }
}
