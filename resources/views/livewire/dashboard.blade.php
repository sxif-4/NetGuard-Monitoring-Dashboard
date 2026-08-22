@php
    $healthCopy = [
        'healthy' => ['label' => __('Healthy'), 'color' => 'green'],
        'warning' => ['label' => __('Warning'), 'color' => 'amber'],
        'critical' => ['label' => __('Critical'), 'color' => 'red'],
    ][$this->networkHealth];

    $statusDotClass = fn (string $color) => match ($color) {
        'green' => 'bg-emerald-400',
        'amber' => 'bg-amber-400',
        'red' => 'bg-rose-400',
        default => 'bg-zinc-400',
    };

    // Auto-scaled unit so a quiet dev/test network (a few Kbps) doesn't
    // just render as a flat, misleading "0 Mbps".
    $bandwidthBps = $this->networkTotals['bandwidth_bps'];
    [$bandwidthValue, $bandwidthUnit] = match (true) {
        $bandwidthBps >= 1_000_000_000 => [round($bandwidthBps / 1_000_000_000, 2), 'Gbps'],
        $bandwidthBps >= 1_000_000 => [round($bandwidthBps / 1_000_000, 1), 'Mbps'],
        $bandwidthBps >= 1_000 => [round($bandwidthBps / 1_000, 1), 'Kbps'],
        default => [$bandwidthBps, 'bps'],
    };
@endphp

<section class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="dark:text-white">{{ __('Overview') }}</flux:heading>
            <flux:subheading>{{ __('Real-time infrastructure health') }}</flux:subheading>
        </div>
        <flux:badge :color="$healthCopy['color']" size="lg">{{ $healthCopy['label'] }}</flux:badge>
    </div>

    {{-- Stat row --}}
    <div class="grid auto-rows-min gap-4 md:grid-cols-4">
        <flux:card class="dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between">
                <flux:text size="sm">{{ __('Total Devices') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-300">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z" />
                    </svg>
                </span>
            </div>
            <flux:heading size="xl" class="mt-3 dark:text-white">{{ $this->deviceCounts['total'] }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                {{ $this->deviceCounts['online'] }} {{ __('online') }} · {{ $this->deviceCounts['warning'] }} {{ __('warn') }} · {{ $this->deviceCounts['offline'] }} {{ __('off') }}
            </flux:text>
        </flux:card>

        <flux:card class="dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between">
                <flux:text size="sm">{{ __('Active Alerts') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 dark:text-amber-300">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </span>
            </div>
            <flux:heading size="xl" class="mt-3 dark:text-white">{{ $this->activeAlertsSummary['total'] }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-rose-500 dark:text-rose-400">
                {{ $this->activeAlertsSummary['critical'] }} {{ __('critical') }}
            </flux:text>
        </flux:card>

        <flux:card class="dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between">
                <flux:text size="sm">{{ __('Aggregate Bandwidth') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-300">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                    </svg>
                </span>
            </div>
            <flux:heading size="xl" class="mt-3 dark:text-white">
                {{ number_format($bandwidthValue) }} <span class="text-sm font-normal text-zinc-500">{{ $bandwidthUnit }}</span>
            </flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">{{ __('across all monitored links') }}</flux:text>
        </flux:card>

        <flux:card class="dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between">
                <flux:text size="sm">{{ __('Avg Latency') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 dark:text-emerald-300">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <flux:heading size="xl" class="mt-3 dark:text-white">
                {{ $this->networkTotals['avg_latency_ms'] ?? '—' }}<span class="text-sm font-normal text-zinc-500">{{ $this->networkTotals['avg_latency_ms'] !== null ? 'ms' : '' }}</span>
            </flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                {{ $this->networkTotals['avg_packet_loss_pct'] ?? '0' }}% {{ __('loss') }}
            </flux:text>
        </flux:card>
    </div>

    {{-- Device health + recent alerts --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="lg:col-span-2 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <flux:heading size="lg" class="dark:text-white">{{ __('Device Health') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('devices.index')" wire:navigate>{{ __('View all') }}</flux:button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-left text-xs text-zinc-500 uppercase dark:border-white/5">
                            <th class="pb-2 font-medium">{{ __('Device') }}</th>
                            <th class="pb-2 font-medium">{{ __('CPU') }}</th>
                            <th class="pb-2 font-medium">{{ __('Memory') }}</th>
                            <th class="pb-2 font-medium">{{ __('Bandwidth') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->deviceHealthRows as $row)
                            <tr class="border-b border-zinc-100 last:border-b-0 dark:border-white/5">
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2 shrink-0 rounded-full {{ $statusDotClass($row['device']->status->color()) }}"></span>
                                        <div>
                                            <flux:text class="font-medium dark:text-zinc-200">{{ $row['device']->name }}</flux:text>
                                            <flux:text size="sm" class="text-zinc-500">{{ $row['device']->ip_address }}</flux:text>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-10 text-zinc-600 dark:text-zinc-300">{{ $row['cpu_pct'] !== null ? round($row['cpu_pct']).'%' : '—' }}</span>
                                        <x-sparkline :points="$row['cpu_series']" color="#f59e0b" class="h-6 w-16" />
                                    </div>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-10 text-zinc-600 dark:text-zinc-300">{{ $row['memory_pct'] !== null ? round($row['memory_pct']).'%' : '—' }}</span>
                                        <x-sparkline :points="$row['memory_series']" color="#a78bfa" class="h-6 w-16" />
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-14 text-zinc-600 dark:text-zinc-300">{{ $row['bandwidth_mbps'] !== null ? number_format($row['bandwidth_mbps'], 1).' M' : '—' }}</span>
                                        <x-sparkline :points="$row['bandwidth_series']" color="#22d3ee" class="h-6 w-16" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-zinc-500">{{ __('No device metrics yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        <flux:card class="dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <flux:heading size="lg" class="dark:text-white">{{ __('Recent Alerts') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('alerts.index')" wire:navigate>{{ __('View all') }}</flux:button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($this->recentAlerts as $alert)
                    <div class="border-b border-zinc-100 pb-3 last:border-b-0 dark:border-white/5">
                        <div class="flex items-center justify-between gap-2">
                            <flux:badge :color="$alert->severity->color()" size="sm">{{ $alert->severity->label() }}</flux:badge>
                            <flux:text size="sm" class="text-zinc-500">{{ $alert->last_detected_at->diffForHumans() }}</flux:text>
                        </div>
                        <flux:text class="mt-1.5 block font-medium dark:text-zinc-200">{{ $alert->title }}</flux:text>
                        <flux:text size="sm" class="text-zinc-500">{{ $alert->device?->name ?? __('Network-wide') }}</flux:text>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">{{ __('No alerts to show.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>

    {{-- Security summary --}}
    <div>
        <flux:heading size="lg" class="dark:text-white">{{ __('Security Summary') }}</flux:heading>
        <div class="mt-2 grid auto-rows-min gap-4 md:grid-cols-5">
            <flux:card class="flex flex-col gap-1 dark:border-white/10 dark:bg-white/[0.03]">
                <flux:text size="sm">{{ __('Anomalies Detected') }}</flux:text>
                <flux:heading size="xl" class="dark:text-white">{{ $this->securitySummary['anomalies_detected'] }}</flux:heading>
            </flux:card>

            <flux:card class="flex flex-col gap-1 dark:border-white/10 dark:bg-white/[0.03]">
                <flux:text size="sm">{{ __('Port Scan Attempts') }}</flux:text>
                <flux:heading size="xl" class="dark:text-white">{{ $this->securitySummary['port_scan_attempts'] }}</flux:heading>
            </flux:card>

            <flux:card class="flex flex-col gap-1 dark:border-white/10 dark:bg-white/[0.03]">
                <flux:text size="sm">{{ __('DoS Activity') }}</flux:text>
                <flux:heading size="xl" class="dark:text-white">{{ $this->securitySummary['dos_activity'] }}</flux:heading>
            </flux:card>

            <flux:card class="flex flex-col gap-1 dark:border-white/10 dark:bg-white/[0.03]">
                <flux:text size="sm">{{ __('Suspicious Behaviour') }}</flux:text>
                <flux:heading size="xl" class="dark:text-white">{{ $this->securitySummary['suspicious_behaviour'] }}</flux:heading>
            </flux:card>

            <flux:card class="flex flex-col gap-1 dark:border-white/10 dark:bg-white/[0.03]">
                <flux:text size="sm">{{ __('High-Risk Events') }}</flux:text>
                <flux:heading size="xl" class="text-red-600 dark:text-rose-400">{{ $this->securitySummary['high_risk_events'] }}</flux:heading>
            </flux:card>
        </div>
    </div>

    <flux:card class="dark:border-white/10 dark:bg-white/[0.03]">
        <x-trend-chart
            chart-id="alert-count"
            :label="__('Alerts (last 30 days)')"
            unit=""
            color="#22d3ee"
            :points="$this->alertCountSeries"
        />
    </flux:card>
</section>
