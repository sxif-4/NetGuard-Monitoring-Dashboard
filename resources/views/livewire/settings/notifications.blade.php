<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Notification settings') }}</flux:heading>

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Configure how alerts reach you — Telegram, Email, or dashboard-only')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <div class="space-y-4 border-b border-zinc-200 pb-6 dark:border-zinc-700">
                <flux:switch wire:model.live="telegramEnabled" :label="__('Telegram notifications')" />

                @if ($telegramEnabled)
                    <flux:input
                        wire:model="telegramBotToken"
                        :label="__('Bot token')"
                        type="password"
                        viewable
                        placeholder="123456:ABC-DEF..."
                    />

                    <flux:input
                        wire:model="telegramChatId"
                        :label="__('Chat ID')"
                        placeholder="-1001234567890"
                    />

                    <flux:button variant="ghost" wire:click="testTelegramConnection">
                        {{ __('Test connection') }}
                    </flux:button>
                @endif
            </div>

            <div class="space-y-4 border-b border-zinc-200 pb-6 dark:border-zinc-700">
                <flux:switch wire:model.live="emailEnabled" :label="__('Email notifications')" />

                @if ($emailEnabled)
                    <flux:input
                        wire:model="notificationEmail"
                        :label="__('Notification email')"
                        type="email"
                        placeholder="admin@example.com"
                    />
                @endif
            </div>

            <div>
                <flux:input
                    wire:model="cooldownSeconds"
                    :label="__('Cooldown between repeat notifications (seconds)')"
                    type="number"
                    min="10"
                    max="3600"
                />
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    {{ __('An alert that stays active won\'t re-notify Telegram/Email more often than this, even across many poll cycles.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
