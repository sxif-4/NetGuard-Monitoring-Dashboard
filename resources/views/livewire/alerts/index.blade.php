<section class="w-full">
    <flux:heading size="xl">{{ __('Alerts') }}</flux:heading>
    <flux:subheading>{{ __('Operational and security alerts across your network') }}</flux:subheading>

    <div class="mt-6 flex flex-wrap gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by title…')" icon="magnifying-glass" class="max-w-xs" />

        <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-48">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="severityFilter" :placeholder="__('All severities')" class="max-w-48">
            <flux:select.option value="">{{ __('All severities') }}</flux:select.option>
            @foreach ($severities as $severity)
                <flux:select.option :value="$severity->value">{{ $severity->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="categoryFilter" :placeholder="__('All categories')" class="max-w-48">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option :value="$category->value">{{ $category->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Device') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Severity') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Last Detected') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($alerts as $alert)
                <flux:table.row wire:key="alert-{{ $alert->id }}">
                    <flux:table.cell class="font-medium">{{ $alert->title }}</flux:table.cell>
                    <flux:table.cell>{{ $alert->device?->name ?? __('—') }}</flux:table.cell>
                    <flux:table.cell>{{ $alert->category->label() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$alert->severity->color()" size="sm">{{ $alert->severity->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$alert->status->color()" size="sm">{{ $alert->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $alert->last_detected_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost" icon="eye" wire:click="view({{ $alert->id }})" :aria-label="__('View')" />

                            @if (in_array($alert->status, [App\Enums\AlertStatus::Detected, App\Enums\AlertStatus::Active]))
                                <flux:button size="sm" variant="filled" wire:click="acknowledge({{ $alert->id }})">
                                    {{ __('Acknowledge') }}
                                </flux:button>
                            @elseif ($alert->status === App\Enums\AlertStatus::Acknowledged)
                                <flux:button size="sm" variant="filled" wire:click="investigate({{ $alert->id }})">
                                    {{ __('Investigate') }}
                                </flux:button>
                                <flux:button size="sm" variant="primary" wire:click="resolve({{ $alert->id }})">
                                    {{ __('Resolve') }}
                                </flux:button>
                            @elseif ($alert->status === App\Enums\AlertStatus::Investigating)
                                <flux:button size="sm" variant="primary" wire:click="resolve({{ $alert->id }})">
                                    {{ __('Resolve') }}
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500">
                        {{ __('No alerts found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $alerts->links() }}
    </div>

    <flux:modal wire:model="viewingId" class="max-w-lg">
        @if ($viewingAlert)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ $viewingAlert->title }}</flux:heading>
                    <flux:subheading>{{ $viewingAlert->description }}</flux:subheading>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:badge :color="$viewingAlert->category->label() === 'Security' ? 'purple' : 'zinc'" size="sm">{{ $viewingAlert->category->label() }}</flux:badge>
                    <flux:badge :color="$viewingAlert->severity->color()" size="sm">{{ $viewingAlert->severity->label() }}</flux:badge>
                    <flux:badge :color="$viewingAlert->status->color()" size="sm">{{ $viewingAlert->status->label() }}</flux:badge>
                </div>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-zinc-500">{{ __('Device') }}</dt>
                    <dd>{{ $viewingAlert->device?->name ?? __('—') }}</dd>

                    <dt class="text-zinc-500">{{ __('Occurrences') }}</dt>
                    <dd>{{ $viewingAlert->occurrences }}</dd>

                    <dt class="text-zinc-500">{{ __('First detected') }}</dt>
                    <dd>{{ $viewingAlert->first_detected_at->diffForHumans() }}</dd>

                    <dt class="text-zinc-500">{{ __('Last detected') }}</dt>
                    <dd>{{ $viewingAlert->last_detected_at->diffForHumans() }}</dd>

                    @if ($viewingAlert->acknowledgedBy)
                        <dt class="text-zinc-500">{{ __('Acknowledged by') }}</dt>
                        <dd>{{ $viewingAlert->acknowledgedBy->name }} ({{ $viewingAlert->acknowledged_at->diffForHumans() }})</dd>
                    @endif

                    @if ($viewingAlert->resolvedBy)
                        <dt class="text-zinc-500">{{ __('Resolved by') }}</dt>
                        <dd>{{ $viewingAlert->resolvedBy->name }} ({{ $viewingAlert->resolved_at->diffForHumans() }})</dd>
                    @endif
                </dl>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</section>
