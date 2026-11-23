<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-theme-script />

        <title>{{ config('app.brand_name', 'Crime Management Operations') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 sm:px-6 lg:px-8">
            <header class="flex flex-wrap items-center justify-between gap-4 py-6">
                <div class="flex items-center text-brand">
                    <x-application-logo class="h-9 w-auto" />
                </div>

                @if (Route::has('login'))
                    <nav class="flex flex-wrap items-center gap-2">
                        <x-theme-toggle />

                        @auth
                            <x-link-button :href="url('/dashboard')" tone="secondary">Dashboard</x-link-button>
                        @else
                            <x-link-button :href="route('login')" tone="secondary">Log in</x-link-button>

                            @if (Route::has('register'))
                                <x-link-button :href="route('register')">Register</x-link-button>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="flex flex-1 items-center py-10">
                <div class="grid w-full gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <section class="rounded-xl border border-line bg-surface p-6 shadow-card sm:p-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand">Secure internal system</p>
                        <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-ink sm:text-4xl">Crime records, evidence, and reports in one workspace.</h1>
                        <p class="mt-4 text-sm leading-6 text-muted">Access is limited to authorized personnel and scoped to your assigned role.</p>

                        @if (Route::has('login'))
                            <div class="mt-8 flex flex-wrap gap-3">
                                @auth
                                    <x-link-button :href="url('/dashboard')">Dashboard</x-link-button>
                                @else
                                    <x-link-button :href="route('login')">Log in</x-link-button>
                                @endauth
                            </div>
                        @endif
                    </section>

                    <div class="relative hidden overflow-hidden rounded-xl bg-brand-dark p-8 text-white dark:bg-surface dark:text-ink lg:flex lg:flex-col lg:justify-between">
                        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-brand/40 blur-3xl dark:bg-brand/20"></div>
                        <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-brand-soft/10 blur-3xl dark:bg-brand/10"></div>

                        <x-application-logo class="relative h-9 w-auto" />

                        <div class="relative">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-soft dark:text-brand">Authorized personnel only</p>
                            <p class="mt-2 text-sm leading-6 text-white/90 dark:text-muted">Access is limited to authorized personnel and scoped to your assigned role.</p>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="border-t border-line py-5 text-xs text-muted">{{ config('app.brand_name', 'Crime Management Operations') }}</footer>
        </div>
    </body>
</html>
