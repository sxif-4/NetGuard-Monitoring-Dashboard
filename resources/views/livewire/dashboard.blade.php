@php
    $healthCopy = [
        'healthy' => ['label' => __('Healthy'), 'color' => 'green'],
        'warning' => ['label' => __('Warning'), 'color' => 'amber'],
        'critical' => ['label' => __('Critical'), 'color' => 'red'],
    ][$this->networkHealth];
@endphp

<section class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <flux:heading size="xl">{{ __('Overview') }}</flux:heading>
    <flux:subheading>{{ __('Network status at a glance') }}</flux:subheading>

    <div class="grid auto-rows-min gap-4 md:grid-cols-5">
        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Total Devices') }}</flux:text>
            <flux:heading size="xl">{{ $this->deviceCounts['total'] }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Online') }}</flux:text>
            <flux:heading size="xl" class="text-green-600 dark:text-green-400">{{ $this->deviceCounts['online'] }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Warning') }}</flux:text>
            <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">{{ $this->deviceCounts['warning'] }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Offline') }}</flux:text>
            <flux:heading size="xl" class="text-red-600 dark:text-red-400">{{ $this->deviceCounts['offline'] }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-2">
            <flux:text size="sm">{{ __('Network Health') }}</flux:text>
            <flux:badge :color="$healthCopy['color']" size="lg">{{ $healthCopy['label'] }}</flux:badge>
        </flux:card>
    </div>

    <flux:card>
        <x-trend-chart
            chart-id="alert-count"
            :label="__('Alerts (last 30 days)')"
            unit=""
            color="#008300"
            :points="$this->alertCountSeries"
        />
    </flux:card>

    <flux:card class="flex-1">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Recent Alerts') }}</flux:heading>
            <flux:button size="sm" variant="ghost" :href="route('alerts.index')" wire:navigate>{{ __('View all') }}</flux:button>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($this->recentAlerts as $alert)
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-3 last:border-b-0 dark:border-zinc-700">
                    <div>
                        <flux:text class="font-medium">{{ $alert->title }}</flux:text>
                        <flux:text size="sm" class="text-zinc-500">
                            {{ $alert->device?->name ?? __('Network-wide') }} · {{ $alert->last_detected_at->diffForHumans() }}
                        </flux:text>
                    </div>

                    <div class="flex gap-2">
                        <flux:badge :color="$alert->severity->color()" size="sm">{{ $alert->severity->label() }}</flux:badge>
                        <flux:badge :color="$alert->status->color()" size="sm">{{ $alert->status->label() }}</flux:badge>
                    </div>
                </div>
            @empty
                <flux:text class="text-zinc-500">{{ __('No alerts to show.') }}</flux:text>
            @endforelse
        </div>
    </flux:card>
</section>
