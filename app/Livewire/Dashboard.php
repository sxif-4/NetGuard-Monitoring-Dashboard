<?php

namespace App\Livewire;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Models\Alert;
use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Overview')]
class Dashboard extends Component
{
    /**
     * @return array<string, int>
     */
    #[Computed]
    public function deviceCounts(): array
    {
        $counts = Device::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total' => (int) $counts->sum(),
            'online' => (int) ($counts[DeviceStatus::Online->value] ?? 0),
            'warning' => (int) ($counts[DeviceStatus::Warning->value] ?? 0),
            'offline' => (int) ($counts[DeviceStatus::Offline->value] ?? 0),
        ];
    }

    #[Computed]
    public function networkHealth(): string
    {
        $hasOfflineDevice = Device::query()
            ->where('monitoring_enabled', true)
            ->where('status', DeviceStatus::Offline)
            ->exists();

        $hasCriticalAlert = Alert::query()
            ->where('status', '!=', AlertStatus::Resolved)
            ->where('severity', AlertSeverity::Critical)
            ->exists();

        if ($hasOfflineDevice || $hasCriticalAlert) {
            return 'critical';
        }

        $hasWarningDevice = Device::query()
            ->where('monitoring_enabled', true)
            ->where('status', DeviceStatus::Warning)
            ->exists();

        $hasElevatedAlert = Alert::query()
            ->where('status', '!=', AlertStatus::Resolved)
            ->whereIn('severity', [AlertSeverity::High, AlertSeverity::Medium])
            ->exists();

        if ($hasWarningDevice || $hasElevatedAlert) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * @return Collection<int, Alert>
     */
    #[Computed]
    public function recentAlerts(): Collection
    {
        return Alert::query()
            ->with('device')
            ->orderByDesc('last_detected_at')
            ->limit(5)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
