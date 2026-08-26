<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'NetGuard') }} — Real-time network monitoring &amp; threat detection</title>

        <link rel="icon" href="/favicon.ico?v=2" sizes="any">
        <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#080b12] font-sans text-zinc-300 antialiased">
        {{-- Ambient background glow --}}
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-40 left-1/4 h-[36rem] w-[36rem] rounded-full bg-cyan-500/10 blur-[120px]"></div>
            <div class="absolute top-1/3 -right-40 h-[30rem] w-[30rem] rounded-full bg-rose-500/10 blur-[120px]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff08_1px,transparent_1px),linear-gradient(to_bottom,#ffffff08_1px,transparent_1px)] bg-[size:56px_56px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,black,transparent)]"></div>
        </div>

        <div class="relative">
            {{-- Nav --}}
            <header class="sticky top-0 z-50 border-b border-white/5 bg-[#080b12]/80 backdrop-blur-lg">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <x-netguard-mark class="size-8" />
                        <span class="text-lg font-semibold tracking-tight text-white">{{ config('app.name', 'NetGuard') }}</span>
                    </a>

                    <div class="hidden items-center gap-8 text-sm text-zinc-400 md:flex">
                        <a href="#features" class="transition hover:text-white">Features</a>
                        <a href="#how-it-works" class="transition hover:text-white">How it works</a>
                        <a href="#stack" class="transition hover:text-white">Under the hood</a>
                    </div>

                    <div class="flex items-center gap-3">
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-900 transition hover:bg-zinc-200"
                            >
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-zinc-400 transition hover:text-white">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="rounded-lg bg-gradient-to-r from-cyan-400 to-rose-400 px-4 py-2 text-sm font-semibold text-zinc-950 shadow-[0_0_20px_-4px_rgba(34,211,238,0.6)] transition hover:opacity-90"
                                >
                                    Get started
                                </a>
                            @endif
                        @endauth
                    </div>
                </nav>
            </header>

            {{-- Hero --}}
            <section class="mx-auto max-w-7xl px-6 pt-20 pb-24 lg:px-8 lg:pt-28">
                <div class="grid items-center gap-16 lg:grid-cols-2">
                    <div>
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-zinc-400">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-cyan-400"></span>
                            </span>
                            ICMP · SNMP · ML-powered anomaly detection
                        </div>

                        <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            See your whole network.
                            <span class="bg-gradient-to-r from-cyan-300 via-cyan-200 to-rose-300 bg-clip-text text-transparent">
                                Catch threats before they spread.
                            </span>
                        </h1>

                        <p class="mt-6 max-w-xl text-lg text-zinc-400">
                            {{ config('app.name', 'NetGuard') }} polls every device in real time, learns what your normal traffic looks like, and flags port scans, DoS activity, and failing hardware the moment they happen — with alerts that reach you in Telegram and email, not just a dashboard.
                        </p>

                        <div class="mt-10 flex flex-wrap items-center gap-4">
                            <a
                                href="{{ Route::has('register') ? route('register') : route('login') }}"
                                class="rounded-lg bg-gradient-to-r from-cyan-400 to-rose-400 px-6 py-3 text-sm font-semibold text-zinc-950 shadow-[0_0_30px_-6px_rgba(34,211,238,0.7)] transition hover:opacity-90"
                            >
                                Start monitoring
                            </a>
                            <a
                                href="#features"
                                class="rounded-lg border border-white/10 px-6 py-3 text-sm font-medium text-zinc-300 transition hover:border-white/25 hover:text-white"
                            >
                                See what it does
                            </a>
                        </div>
                    </div>

                    {{-- Live-look dashboard preview card, matching the real Overview page's content --}}
                    <div class="relative">
                        <div class="absolute -inset-6 rounded-3xl bg-gradient-to-br from-cyan-500/10 to-rose-500/10 blur-2xl"></div>
                        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-[#0c1018] shadow-2xl">
                            <div class="flex items-center justify-between border-b border-white/5 px-5 py-3.5">
                                <div>
                                    <span class="text-sm font-medium text-white">Overview</span>
                                    <span class="ml-2 text-[11px] text-zinc-500">Real-time infrastructure health</span>
                                </div>
                                <span class="rounded-md bg-rose-500/15 px-2 py-1 text-[10px] font-medium text-rose-400">Critical</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 p-5">
                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-3">
                                    <div class="flex items-start justify-between">
                                        <p class="text-[11px] text-zinc-500">Total Devices</p>
                                        <span class="flex size-5 items-center justify-center rounded-md bg-cyan-500/15 text-cyan-400">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3"><rect x="2" y="3" width="20" height="8" rx="2" /><rect x="2" y="13" width="20" height="8" rx="2" /><circle cx="6" cy="7" r="0.6" fill="currentColor" /><circle cx="6" cy="17" r="0.6" fill="currentColor" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xl font-semibold text-white">18</p>
                                    <p class="mt-0.5 text-[11px] text-emerald-400">15 online</p>
                                </div>
                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-3">
                                    <div class="flex items-start justify-between">
                                        <p class="text-[11px] text-zinc-500">Active Alerts</p>
                                        <span class="flex size-5 items-center justify-center rounded-md bg-amber-500/15 text-amber-400">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3"><path d="M12 3 2 20h20L12 3Z" /><path d="M12 10v4M12 17.5v.01" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xl font-semibold text-white">4</p>
                                    <p class="mt-0.5 text-[11px] text-rose-400">1 critical</p>
                                </div>
                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-3">
                                    <div class="flex items-start justify-between">
                                        <p class="text-[11px] text-zinc-500">Aggregate Bandwidth</p>
                                        <span class="flex size-5 items-center justify-center rounded-md bg-cyan-500/15 text-cyan-400">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3"><path d="M2 9a15 15 0 0 1 20 0M5.5 13a9 9 0 0 1 13 0M9 17a4 4 0 0 1 6 0" /><circle cx="12" cy="20" r="0.8" fill="currentColor" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xl font-semibold text-white">32 <span class="text-xs font-normal text-zinc-500">Mbps</span></p>
                                    <p class="mt-0.5 text-[11px] text-zinc-500">across all links</p>
                                </div>
                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-3">
                                    <div class="flex items-start justify-between">
                                        <p class="text-[11px] text-zinc-500">Avg Latency</p>
                                        <span class="flex size-5 items-center justify-center rounded-md bg-emerald-500/15 text-emerald-400">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xl font-semibold text-white">18 <span class="text-xs font-normal text-zinc-500">ms</span></p>
                                    <p class="mt-0.5 text-[11px] text-zinc-500">0.4% loss</p>
                                </div>
                            </div>

                            {{-- Recent Alerts --}}
                            <div class="space-y-2 px-5 pb-5">
                                <p class="px-0.5 pb-1 text-[11px] font-medium tracking-wide text-zinc-500 uppercase">Recent Alerts</p>
                                <div class="rounded-lg border border-rose-500/20 bg-rose-500/5 px-3 py-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-[10px] font-medium text-rose-400">Critical</span>
                                        <span class="text-[10px] text-zinc-500">2m ago</span>
                                    </div>
                                    <p class="mt-1.5 text-xs font-medium text-zinc-200">Device Offline</p>
                                    <p class="text-[11px] text-zinc-500">core-router-01</p>
                                </div>
                                <div class="rounded-lg border border-amber-500/20 bg-amber-500/5 px-3 py-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-medium text-amber-400">High</span>
                                        <span class="text-[10px] text-zinc-500">14m ago</span>
                                    </div>
                                    <p class="mt-1.5 text-xs font-medium text-zinc-200">High Latency</p>
                                    <p class="text-[11px] text-zinc-500">dns-resolver-2</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Feature grid --}}
            <section id="features" class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Everything a NOC actually needs</h2>
                    <p class="mt-4 text-zinc-400">Not a demo. Real ICMP/SNMP polling, a trained ML pipeline, and alerts that route by severity — running today.</p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $features = [
                            [
                                'icon' => 'M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07-2.83 2.83M9.76 14.24l-2.83 2.83m0-10.14 2.83 2.83m4.48 4.48 2.83 2.83',
                                'title' => 'Real-time monitoring',
                                'body' => 'ICMP latency/packet-loss and SNMP CPU, memory, and bandwidth polling on a fixed cadence, with automatic online/offline status.',
                            ],
                            [
                                'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547Z',
                                'title' => 'ML anomaly detection',
                                'body' => 'A Random Forest classifies known attack patterns; an Isolation Forest catches traffic that just looks wrong — port scans, DoS shapes, and more.',
                            ],
                            [
                                'icon' => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
                                'title' => 'Instant alerts',
                                'body' => 'Critical alerts reach Telegram and email; high severity reaches Telegram; everything shows up on the dashboard — with a cooldown so nothing spams you.',
                            ],
                            [
                                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                                'title' => 'Historical analytics',
                                'body' => '1h/24h/7d/30d trend charts for every metric per device, plus a 30-day alert-volume trend on the Overview dashboard.',
                            ],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <div class="group rounded-2xl border border-white/10 bg-white/[0.02] p-6 transition hover:border-white/20 hover:bg-white/[0.04]">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500/15 to-rose-500/15">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 text-cyan-300">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-sm font-semibold text-white">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $feature['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- How it works --}}
            <section id="how-it-works" class="border-t border-white/5 bg-white/[0.015] py-24">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">From "added a device" to "resolved"</h2>
                        <p class="mt-4 text-zinc-400">Two services, one loop, running continuously.</p>
                    </div>

                    <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $steps = [
                                ['n' => '01', 'title' => 'Add a device', 'body' => 'Name it, give it an IP, choose ICMP, SNMP, or TCP.'],
                                ['n' => '02', 'title' => 'Poll & watch', 'body' => 'A Python daemon checks it every cycle and scores live traffic against the ML models.'],
                                ['n' => '03', 'title' => 'Detect & alert', 'body' => 'Breaches and anomalies become deduplicated alerts, routed by severity to Telegram, email, or just the dashboard.'],
                                ['n' => '04', 'title' => 'Review & resolve', 'body' => 'Acknowledge, investigate, and resolve from the Alerts screen — with full history kept for trend analysis.'],
                            ];
                        @endphp

                        @foreach ($steps as $step)
                            <div class="relative pl-6">
                                <div class="absolute top-1 left-0 h-full w-px bg-gradient-to-b from-cyan-400/50 to-transparent"></div>
                                <span class="bg-gradient-to-r from-cyan-300 to-rose-300 bg-clip-text text-xs font-semibold tracking-widest text-transparent">{{ $step['n'] }}</span>
                                <h3 class="mt-2 text-sm font-semibold text-white">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $step['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Stack --}}
            <section id="stack" class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-10 text-center">
                    <h2 class="text-2xl font-semibold text-white">Built on a real, boring-in-a-good-way stack</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-zinc-400">
                        Laravel &amp; Livewire for the control plane, a Python/FastAPI daemon for polling and inference, PostgreSQL and Redis underneath.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-8 gap-y-4 text-sm font-medium text-zinc-500">
                        <span>Laravel 13</span>
                        <span class="text-white/10">·</span>
                        <span>Livewire</span>
                        <span class="text-white/10">·</span>
                        <span>Python / FastAPI</span>
                        <span class="text-white/10">·</span>
                        <span>scikit-learn</span>
                        <span class="text-white/10">·</span>
                        <span>PostgreSQL</span>
                        <span class="text-white/10">·</span>
                        <span>Redis</span>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            <section class="mx-auto max-w-7xl px-6 pb-24 lg:px-8">
                <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-cyan-500/10 via-transparent to-rose-500/10 px-10 py-16 text-center">
                    <h2 class="text-3xl font-semibold tracking-tight text-white">Stop finding out about outages from your users.</h2>
                    <p class="mx-auto mt-4 max-w-xl text-zinc-400">Get your devices onto the dashboard in minutes.</p>
                    <a
                        href="{{ Route::has('register') ? route('register') : route('login') }}"
                        class="mt-8 inline-block rounded-lg bg-gradient-to-r from-cyan-400 to-rose-400 px-6 py-3 text-sm font-semibold text-zinc-950 shadow-[0_0_30px_-6px_rgba(34,211,238,0.7)] transition hover:opacity-90"
                    >
                        Get started free
                    </a>
                </div>
            </section>

            {{-- Footer --}}
            <footer class="border-t border-white/5 px-6 py-10 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2">
                        <x-netguard-mark class="size-6" />
                        <span class="text-sm font-medium text-zinc-400">{{ config('app.name', 'NetGuard') }}</span>
                    </div>
                    <p class="text-xs text-zinc-600">&copy; {{ date('Y') }} {{ config('app.name', 'NetGuard') }}. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
