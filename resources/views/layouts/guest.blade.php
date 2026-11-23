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
        <div class="min-h-screen bg-canvas lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div class="relative hidden overflow-hidden bg-brand-dark p-12 text-white dark:bg-surface dark:text-ink lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-brand/40 blur-3xl dark:bg-brand/20"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-brand-soft/10 blur-3xl dark:bg-brand/10"></div>

                <a href="{{ route('dashboard') }}" class="relative block">
                    <x-application-logo class="h-10 w-auto" />
                </a>

                <div class="relative max-w-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-soft dark:text-brand">Secure internal system</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight">Crime records, evidence, and reports in one workspace.</h1>
                    <p class="mt-4 text-sm leading-6 text-white/90 dark:text-muted">Access is limited to authorized personnel and scoped to your assigned role.</p>
                </div>

                <p class="relative text-xs text-brand-soft/70 dark:text-muted">Authorized personnel only</p>
            </div>

            <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:min-h-0">
                <div class="w-full max-w-md">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="text-brand lg:hidden">
                            <x-application-logo class="h-9 w-auto" />
                        </div>

                        <x-theme-toggle class="ml-auto" />
                    </div>

                    <div class="rounded-xl border border-line bg-surface p-6 shadow-card sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
