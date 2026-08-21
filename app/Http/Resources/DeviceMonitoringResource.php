<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class DeviceMonitoringResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'monitoring_method' => $this->monitoring_method->value,
            'monitoring_port' => $this->monitoring_port,
            'snmp_community' => $this->snmp_community,
            'snmp_version' => $this->snmp_version,
            'snmp_port' => $this->snmp_port,
        ];
    }
}
