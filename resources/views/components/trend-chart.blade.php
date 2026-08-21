@props([
    'chartId',
    'label',
    'points' => [],
    'unit' => '',
    'color' => '#2a78d6',
])

<div class="flex flex-col gap-2">
    <flux:subheading>{{ $label }}</flux:subheading>

    <div
        wire:ignore
        wire:key="chart-{{ $chartId }}"
        x-data="trendChart({
            chartId: @js($chartId),
            label: @js($label),
            unit: @js($unit),
            color: @js($color),
            points: @js($points),
        })"
        x-init="init()"
        class="relative h-56 w-full"
    >
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
