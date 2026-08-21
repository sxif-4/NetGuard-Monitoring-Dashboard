<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeviceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceMetricsRequest;
use App\Http\Resources\DeviceMonitoringResource;
use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    /**
     * List the devices the daemon should poll this cycle.
     */
    public function devices(): AnonymousResourceCollection
    {
        $devices = Device::query()
            ->where('monitoring_enabled', true)
            ->get();

        return DeviceMonitoringResource::collection($devices);
    }

    /**
     * Ingest a batch of polled metrics, updating each device's live status.
     */
    public function storeMetrics(StoreDeviceMetricsRequest $request): JsonResponse
    {
        $entries = $request->validated('metrics');

        $accepted = DB::transaction(function () use ($entries) {
            $count = 0;

            foreach ($entries as $entry) {
                $device = Device::find((int) $entry['device_id']);

                if ($device === null) {
                    continue;
                }

                DeviceMetric::create([
                    'device_id' => $device->id,
                    'reachable' => $entry['reachable'],
                    'latency_ms' => $entry['latency_ms'] ?? null,
                    'packet_loss_pct' => $entry['packet_loss_pct'] ?? null,
                    'cpu_pct' => $entry['cpu_pct'] ?? null,
                    'memory_pct' => $entry['memory_pct'] ?? null,
                    'bandwidth_pct' => $entry['bandwidth_pct'] ?? null,
                    'bandwidth_in_bps' => $entry['bandwidth_in_bps'] ?? null,
                    'bandwidth_out_bps' => $entry['bandwidth_out_bps'] ?? null,
                    'uptime_seconds' => $entry['uptime_seconds'] ?? null,
                    'error' => $entry['error'] ?? null,
                    'polled_at' => $entry['polled_at'],
                ]);

                $device->status = $entry['reachable'] ? DeviceStatus::Online : DeviceStatus::Offline;

                if ($entry['reachable']) {
                    $device->last_seen_at = $entry['polled_at'];
                }

                $device->save();

                $count++;
            }

            return $count;
        });

        return response()->json(['accepted' => $accepted], 201);
    }
}
