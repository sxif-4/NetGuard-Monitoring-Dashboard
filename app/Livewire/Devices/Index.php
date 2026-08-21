<?php

namespace App\Livewire\Devices;

use App\Concerns\DeviceValidationRules;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\MonitoringMethod;
use App\Models\Device;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Devices')]
class Index extends Component
{
    use DeviceValidationRules, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $typeFilter = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $type = '';

    public string $ipAddress = '';

    public string $monitoringMethod = '';

    public ?int $monitoringPort = null;

    public bool $monitoringEnabled = true;

    public string $location = '';

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'type', 'ipAddress', 'monitoringMethod', 'monitoringPort', 'monitoringEnabled', 'location']);
        $this->monitoringEnabled = true;
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function edit(int $deviceId): void
    {
        $device = Device::findOrFail($deviceId);

        $this->editingId = $device->id;
        $this->name = $device->name;
        $this->type = $device->type->value;
        $this->ipAddress = $device->ip_address;
        $this->monitoringMethod = $device->monitoring_method->value;
        $this->monitoringPort = $device->monitoring_port;
        $this->monitoringEnabled = $device->monitoring_enabled;
        $this->location = (string) $device->location;

        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate($this->deviceRules($this->editingId));

        $attributes = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'ip_address' => $validated['ipAddress'],
            'monitoring_method' => $validated['monitoringMethod'],
            'monitoring_port' => $validated['monitoringPort'],
            'monitoring_enabled' => $this->monitoringEnabled,
            'location' => $validated['location'],
        ];

        if ($this->editingId === null) {
            $attributes['status'] = DeviceStatus::Unknown->value;
            Device::create($attributes);
            Flux::toast(variant: 'success', text: __('Device added.'));
        } else {
            Device::findOrFail($this->editingId)->update($attributes);
            Flux::toast(variant: 'success', text: __('Device updated.'));
        }

        $this->showFormModal = false;
    }

    public function toggleMonitoring(int $deviceId): void
    {
        $device = Device::findOrFail($deviceId);
        $device->monitoring_enabled = ! $device->monitoring_enabled;

        if (! $device->monitoring_enabled) {
            $device->status = DeviceStatus::Unknown;
        }

        $device->save();

        Flux::toast(variant: 'success', text: $device->monitoring_enabled
            ? __('Monitoring enabled for :device.', ['device' => $device->name])
            : __('Monitoring disabled for :device.', ['device' => $device->name]));
    }

    public function confirmDelete(int $deviceId): void
    {
        $this->confirmingDeleteId = $deviceId;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $device = Device::findOrFail($this->confirmingDeleteId);
        $device->delete();

        $this->confirmingDeleteId = null;

        Flux::toast(variant: 'success', text: __('Device removed.'));
    }

    public function render(): View
    {
        $devices = Device::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('type', $this->typeFilter))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.devices.index', [
            'devices' => $devices,
            'deviceTypes' => DeviceType::cases(),
            'monitoringMethods' => MonitoringMethod::cases(),
            'deviceStatuses' => DeviceStatus::cases(),
        ]);
    }
}
