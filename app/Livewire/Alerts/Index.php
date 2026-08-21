<?php

namespace App\Livewire\Alerts;

use App\Enums\AlertCategory;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Alerts')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $severityFilter = '';

    #[Url]
    public string $categoryFilter = '';

    public ?int $viewingId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function view(int $alertId): void
    {
        $this->viewingId = $alertId;
    }

    public function acknowledge(int $alertId): void
    {
        $alert = Alert::findOrFail($alertId);

        if (! in_array($alert->status, [AlertStatus::Detected, AlertStatus::Active], true)) {
            return;
        }

        $alert->update([
            'status' => AlertStatus::Acknowledged,
            'acknowledged_by' => Auth::id(),
            'acknowledged_at' => now(),
        ]);

        Flux::toast(variant: 'success', text: __('Alert acknowledged.'));
    }

    public function investigate(int $alertId): void
    {
        $alert = Alert::findOrFail($alertId);

        if ($alert->status !== AlertStatus::Acknowledged) {
            return;
        }

        $alert->update(['status' => AlertStatus::Investigating]);

        Flux::toast(variant: 'success', text: __('Alert marked as investigating.'));
    }

    public function resolve(int $alertId): void
    {
        $alert = Alert::findOrFail($alertId);

        if (! in_array($alert->status, [AlertStatus::Acknowledged, AlertStatus::Investigating], true)) {
            return;
        }

        $alert->update([
            'status' => AlertStatus::Resolved,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        Flux::toast(variant: 'success', text: __('Alert resolved.'));
    }

    public function render(): View
    {
        $alerts = Alert::query()
            ->with('device')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->severityFilter !== '', fn ($query) => $query->where('severity', $this->severityFilter))
            ->when($this->categoryFilter !== '', fn ($query) => $query->where('category', $this->categoryFilter))
            ->orderByDesc('last_detected_at')
            ->paginate(15);

        return view('livewire.alerts.index', [
            'alerts' => $alerts,
            'viewingAlert' => $this->viewingId ? Alert::with(['device', 'acknowledgedBy', 'resolvedBy'])->find($this->viewingId) : null,
            'statuses' => AlertStatus::cases(),
            'severities' => AlertSeverity::cases(),
            'categories' => AlertCategory::cases(),
        ]);
    }
}
