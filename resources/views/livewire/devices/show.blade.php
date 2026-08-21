<section class="w-full">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $device->name }}</flux:heading>
            <flux:subheading>{{ $device->ip_address }} &middot; {{ $device->type->label() }}</flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('devices.index')" wire:navigate>
            {{ __('Back to Devices') }}
        </flux:button>
    </div>

    <div class="mt-6 grid auto-rows-min gap-4 md:grid-cols-4">
        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Status') }}</flux:text>
            <flux:badge :color="$device->status->color()" size="lg" class="w-fit">{{ $device->status->label() }}</flux:badge>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Last Seen') }}</flux:text>
            <flux:heading size="lg">{{ $device->last_seen_at?->diffForHumans() ?? __('Never') }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Monitoring Method') }}</flux:text>
            <flux:heading size="lg">{{ $device->monitoring_method->label() }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:text size="sm">{{ __('Uptime') }}</flux:text>
            <flux:heading size="lg">{{ $this->uptimeLabel ?? __('—') }}</flux:heading>
        </flux:card>
    </div>

    <div class="mt-8 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Historical Metrics') }}</flux:heading>

        <flux:select wire:model.live="range" class="max-w-40">
            <flux:select.option value="1h">{{ __('Last 1 Hour') }}</flux:select.option>
            <flux:select.option value="24h">{{ __('Last 24 Hours') }}</flux:select.option>
            <flux:select.option value="7d">{{ __('Last 7 Days') }}</flux:select.option>
            <flux:select.option value="30d">{{ __('Last 30 Days') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="mt-4 grid gap-6 md:grid-cols-2">
        @foreach ($this->charts() as $chartId => [$field, $label, $unit, $color])
            <flux:card>
                <x-trend-chart
                    :chart-id="$chartId"
                    :label="$label"
                    :unit="$unit"
                    :color="$color"
                    :points="$this->chartPoints[$chartId]"
                />
            </flux:card>
        @endforeach
    </div>
</section>
