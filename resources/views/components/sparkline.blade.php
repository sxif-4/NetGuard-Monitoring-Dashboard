@props([
    'points' => [],
    'color' => '#22d3ee',
    'width' => 100,
    'height' => 28,
])

@php
    $values = array_values(array_filter($points, fn ($value) => $value !== null));
    $count = count($values);
    $min = $count ? min($values) : 0;
    $max = $count ? max($values) : 1;
    $range = ($max - $min) ?: 1;

    $coords = [];
    foreach ($values as $index => $value) {
        $x = $count > 1 ? ($index / ($count - 1)) * $width : $width / 2;
        $y = $height - (($value - $min) / $range) * $height;
        $coords[] = round($x, 1).','.round($y, 1);
    }
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" {{ $attributes->class(['overflow-visible']) }}>
    @if (count($coords) > 1)
        <polyline
            fill="none"
            stroke="{{ $color }}"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
            points="{{ implode(' ', $coords) }}"
        />
    @else
        <line x1="0" y1="{{ $height / 2 }}" x2="{{ $width }}" y2="{{ $height / 2 }}" stroke="currentColor" stroke-width="1" stroke-dasharray="2 3" class="text-zinc-300 dark:text-zinc-700" />
    @endif
</svg>
