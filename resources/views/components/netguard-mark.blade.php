@props([
    'class' => '',
])

@php
    // Unique per render so multiple copies on one page (nav, hero, footer)
    // don't collide on the same <linearGradient id>.
    $gradientId = 'netguard-mark-'.Str::random(8);
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="{{ $class }}" {{ $attributes }}>
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="0" y1="100" x2="100" y2="0">
            <stop offset="0%" stop-color="#22d3ee" />
            <stop offset="100%" stop-color="#fb7185" />
        </linearGradient>
    </defs>

    <path fill="none" stroke="url(#{{ $gradientId }})" stroke-width="4" d="M50 4 89.5 27 89.5 73 50 96 10.5 73 10.5 27Z" />
    <path fill="none" stroke="#334155" stroke-width="1.5" stroke-dasharray="3 3" d="M50 15 76 30.5 76 61.5 50 77 24 61.5 24 30.5Z" />

    <path
        fill="none"
        stroke="url(#{{ $gradientId }})"
        stroke-width="6"
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M33 72 33 32 67 64 67 28"
    />

    <circle cx="33" cy="72" r="4" fill="#22d3ee" />
    <circle cx="50" cy="48" r="3.5" fill="#e2e8f0" />
    <circle cx="67" cy="28" r="4" fill="#fb7185" />
</svg>
