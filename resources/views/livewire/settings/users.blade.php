<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('User management') }}</flux:heading>

    <x-settings.layout :heading="__('User Management')" :subheading="__('Manage who can access NetGuard and what they can do')" :wide="true">
        <div class="my-6 flex justify-end">
            <flux:button variant="primary" icon="plus" wire:click="create">
                {{ __('Add User') }}
            </flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Joined') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row wire:key="user-{{ $user->id }}">
                        <flux:table.cell class="font-medium">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <flux:badge size="sm" color="zinc">{{ __('You') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:select
                                size="sm"
                                class="max-w-36"
                                :disabled="$user->id === auth()->id()"
                                wire:change="updateRole({{ $user->id }}, $event.target.value)"
                            >
                                @foreach ($roles as $role)
                                    <flux:select.option :value="$role->value" :selected="$user->role === $role">{{ $role->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $user->created_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    :disabled="$user->id === auth()->id()"
                                    wire:click="confirmDelete({{ $user->id }})"
                                    :aria-label="__('Remove')"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-settings.layout>

    <flux:modal wire:model="showFormModal" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ __('Add User') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />

            <flux:input wire:model="email" :label="__('Email')" type="email" required />

            <flux:select wire:model="role" :label="__('Role')" required>
                @foreach ($roles as $roleOption)
                    <flux:select.option :value="$roleOption->value">{{ $roleOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="password" :label="__('Password')" type="password" viewable required />

            <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" viewable required />

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
            <flux:heading size="lg">{{ __('Remove this user?') }}</flux:heading>
            <flux:subheading>{{ __('They will immediately lose access to NetGuard.') }}</flux:subheading>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Remove') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
