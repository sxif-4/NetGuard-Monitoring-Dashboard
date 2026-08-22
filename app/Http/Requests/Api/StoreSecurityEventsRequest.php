<?php

namespace App\Http\Requests\Api;

use App\Enums\SecurityAlertType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSecurityEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unlike StoreDeviceMetricsRequest, there's no device_id here at all —
     * scanned/attacking IPs frequently don't map to any monitored Device row,
     * so SecurityAlertEvaluator resolves that correlation itself, optionally,
     * from source_ip/destination_ip.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1'],
            'events.*.type' => ['required', Rule::enum(SecurityAlertType::class)],
            'events.*.source_ip' => ['required', 'ip'],
            'events.*.destination_ip' => ['nullable', 'ip'],
            'events.*.source_port' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'events.*.destination_port' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'events.*.protocol' => ['required', Rule::in(['TCP', 'UDP', 'ICMP', 'OTHER'])],
            'events.*.model' => ['required', Rule::in(['random_forest', 'isolation_forest'])],
            'events.*.anomaly_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'events.*.possible_activity' => ['required', 'string'],
            'events.*.detected_at' => ['required', 'date'],
        ];
    }
}
