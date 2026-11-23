<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-theme-script />

        <title>{{ config('app.brand_name', 'Crime Management Operations') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-canvas print:min-h-0" x-data="{ sidebarOpen: false }">
            <div class="print:hidden">
                @include('layouts.navigation')
            </div>

            <div class="min-w-0 lg:pl-[250px] print:pl-0">
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-line bg-surface px-4 sm:px-6 print:hidden">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-muted transition hover:bg-canvas hover:text-ink focus:outline-none focus:ring-2 focus:ring-brand lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation">
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>

                        <div class="min-w-0">
                            @isset($header)
                                {{ $header }}
                            @else
                                <h1 class="truncate text-base font-semibold text-ink">{{ config('app.brand_name', 'Crime Management Operations') }}</h1>
                            @endisset
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <x-theme-toggle />

                        <span class="hidden rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand-ink dark:text-brand sm:inline-flex">
                            {{ \Illuminate\Support\Str::headline(Auth::user()->role?->value ?? 'user') }}
                        </span>

                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-left transition hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-sm font-semibold text-slate-950" title="{{ Auth::user()->name }}">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </span>
                                    <span class="hidden sm:block">
                                        <span class="block max-w-[140px] truncate text-sm font-semibold text-ink">{{ Auth::user()->name }}</span>
                                        <span class="block text-xs text-muted">Account</span>
                                    </span>
                                    <x-icon name="logout" class="hidden h-4 w-4 text-muted sm:block" />
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="border-b border-line px-4 py-3 sm:hidden">
                                    <p class="truncate text-sm font-semibold text-ink">{{ Auth::user()->name }}</p>
                                    <p class="truncate text-xs text-muted">{{ Auth::user()->email }}</p>
                                </div>

                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
