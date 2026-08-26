<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-[#080b12]">
        <div class="pointer-events-none fixed inset-0 -z-10 hidden dark:block">
            <div class="absolute -top-40 left-1/4 h-[36rem] w-[36rem] rounded-full bg-cyan-500/10 blur-[120px]"></div>
            <div class="absolute top-1/3 -right-40 h-[30rem] w-[30rem] rounded-full bg-rose-500/10 blur-[120px]"></div>
        </div>

        <div x-data class="fixed top-4 end-4 z-50 flex items-center gap-2">
            <flux:button
                size="sm"
                variant="ghost"
                icon="arrows-pointing-out"
                x-on:click="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
            />

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button
                    size="sm"
                    variant="ghost"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    data-test="logout-button"
                />
            </form>
        </div>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
