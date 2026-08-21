<section class="w-full">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Devices') }}</flux:heading>
            <flux:subheading>{{ __('Manage monitored network devices') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">
            {{ __('Add Device') }}
        </flux:button>
    </div>

    <div class="mt-6 flex flex-wrap gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or IP…')" icon="magnifying-glass" class="max-w-xs" />

        <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-48">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($deviceStatuses as $status)
                <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="typeFilter" :placeholder="__('All types')" class="max-w-48">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            @foreach ($deviceTypes as $type)
                <flux:select.option :value="$type->value">{{ $type->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('IP Address') }}</flux:table.column>
            <flux:table.column>{{ __('Method') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Monitoring') }}</flux:table.column>
            <flux:table.column>{{ __('Last Seen') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($devices as $device)
                <flux:table.row wire:key="device-{{ $device->id }}">
                    <flux:table.cell class="font-medium">{{ $device->name }}</flux:table.cell>
                    <flux:table.cell>{{ $device->type->label() }}</flux:table.cell>
                    <flux:table.cell>{{ $device->ip_address }}</flux:table.cell>
                    <flux:table.cell>{{ $device->monitoring_method->label() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$device->status->color()" size="sm">{{ $device->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:click="toggleMonitoring({{ $device->id }})" :checked="$device->monitoring_enabled" />
                    </flux:table.cell>
                    <flux:table.cell>{{ $device->last_seen_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost" icon="eye" :href="route('devices.show', $device)" wire:navigate :aria-label="__('View')" />
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="edit({{ $device->id }})" :aria-label="__('Edit')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $device->id }})" :aria-label="__('Delete')" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500">
                        {{ __('No devices found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $devices->links() }}
    </div>

    <flux:modal wire:model="showFormModal" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Device') : __('Add Device') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />

            <flux:select wire:model="type" :label="__('Device Type')" required>
                <flux:select.option value="">{{ __('Select a type') }}</flux:select.option>
                @foreach ($deviceTypes as $deviceType)
                    <flux:select.option :value="$deviceType->value">{{ $deviceType->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="ipAddress" :label="__('IP Address')" required />

            <flux:select wire:model="monitoringMethod" :label="__('Monitoring Method')" required>
                <flux:select.option value="">{{ __('Select a method') }}</flux:select.option>
                @foreach ($monitoringMethods as $method)
                    <flux:select.option :value="$method->value">{{ $method->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="monitoringPort" :label="__('Monitoring Port')" type="number" :description="__('Optional — required for SNMP/TCP checks')" />

            <flux:input wire:model="location" :label="__('Location')" :description="__('Optional')" />

            <flux:switch wire:model="monitoringEnabled" :label="__('Monitoring enabled')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="confirmingDeleteId" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Remove this device?') }}</flux:heading>
            <flux:subheading>{{ __('This will permanently delete the device and its alert history.') }}</flux:subheading>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Remove') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
