<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Threshold settings') }}</flux:heading>

    <x-settings.layout :heading="__('Thresholds')" :subheading="__('Configure warning and critical thresholds used for alerting')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            @foreach ($metrics as $metric)
                <div class="grid grid-cols-2 gap-4 border-b border-zinc-200 pb-6 last:border-b-0 dark:border-zinc-700">
                    <div class="col-span-2">
                        <flux:heading size="sm">{{ $metric->label() }}</flux:heading>
                    </div>

                    <flux:input
                        wire:model="thresholds.{{ $metric->value }}.warning"
                        :label="__('Warning (:unit)', ['unit' => $metric->unit()])"
                        type="number"
                        step="0.01"
                    />

                    @if ($metric->hasCriticalThreshold())
                        <flux:input
                            wire:model="thresholds.{{ $metric->value }}.critical"
                            :label="__('Critical (:unit)', ['unit' => $metric->unit()])"
                            type="number"
                            step="0.01"
                        />
                    @endif
                </div>
            @endforeach

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
