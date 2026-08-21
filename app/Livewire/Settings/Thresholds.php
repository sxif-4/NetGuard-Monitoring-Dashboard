<?php

namespace App\Livewire\Settings;

use App\Enums\ThresholdMetric;
use App\Models\Threshold;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Threshold settings')]
class Thresholds extends Component
{
    /**
     * @var array<string, array{warning: float|null, critical: float|null, unit: string, label: string}>
     */
    public array $thresholds = [];

    public function mount(): void
    {
        $this->thresholds = Threshold::query()
            ->get()
            ->mapWithKeys(fn (Threshold $threshold) => [
                $threshold->metric->value => [
                    'warning' => $threshold->warning_threshold,
                    'critical' => $threshold->critical_threshold,
                    'unit' => $threshold->unit,
                    'label' => $threshold->label,
                ],
            ])
            ->all();
    }

    public function save(): void
    {
        $rules = [];

        foreach (ThresholdMetric::cases() as $metric) {
            $rules["thresholds.{$metric->value}.warning"] = ['required', 'numeric', 'min:0'];

            $rules["thresholds.{$metric->value}.critical"] = $metric->hasCriticalThreshold()
                ? ['required', 'numeric', 'gt:thresholds.'.$metric->value.'.warning']
                : ['nullable'];
        }

        $this->validate($rules);

        foreach ($this->thresholds as $metric => $values) {
            Threshold::where('metric', $metric)->update([
                'warning_threshold' => $values['warning'],
                'critical_threshold' => ThresholdMetric::from($metric)->hasCriticalThreshold() ? $values['critical'] : null,
            ]);
        }

        Flux::toast(variant: 'success', text: __('Thresholds updated.'));
    }

    public function render(): View
    {
        return view('livewire.settings.thresholds', [
            'metrics' => ThresholdMetric::cases(),
        ]);
    }
}
