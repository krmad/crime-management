<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard - Crime Management System</title>

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-64 shrink-0 flex-col bg-slate-900 text-slate-300 dark:bg-slate-950 lg:flex">
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                <svg class="h-8 w-8 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linejoin="round" d="M12 3 4.5 6v5.25c0 4.6 3.1 8.3 7.5 9.75 4.4-1.45 7.5-5.15 7.5-9.75V6L12 3Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-white">Crime Management</p>
                    <p class="text-xs text-slate-400">Internal System</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-6">
                @foreach ($navigation as $item)
                    @if ($item['url'])
                        <a
                            href="{{ $item['url'] }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ $item['label'] === 'Dashboard' ? 'bg-indigo-600 text-white' : 'hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linejoin="round" d="M3.75 6.75h4.5v4.5h-4.5zM15.75 6.75h4.5v4.5h-4.5zM3.75 15h4.5v4.5h-4.5zM15.75 15h4.5v4.5h-4.5z" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linejoin="round" d="M3.75 6.75h4.5v4.5h-4.5zM15.75 6.75h4.5v4.5h-4.5zM3.75 15h4.5v4.5h-4.5zM15.75 15h4.5v4.5h-4.5z" />
                            </svg>
                            {{ $item['label'] }}
                        </span>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-white/10 px-6 py-4 text-xs text-slate-500">
                Authorized personnel only
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <svg class="h-7 w-7 text-indigo-600 dark:text-indigo-400 lg:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linejoin="round" d="M12 3 4.5 6v5.25c0 4.6 3.1 8.3 7.5 9.75 4.4-1.45 7.5-5.15 7.5-9.75V6L12 3Z" />
                    </svg>
                    <h1 class="text-base font-semibold text-slate-900 sm:text-lg dark:text-white">Crime Management System</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 sm:inline dark:bg-slate-800 dark:text-slate-300">{{ \Illuminate\Support\Str::headline(Auth::user()->role?->value ?? 'user') }}</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white" title="{{ Auth::user()->name }}">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                </div>
            </header>

            <nav class="flex gap-2 overflow-x-auto border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 lg:hidden">
                @foreach ($navigation as $item)
                    @if ($item['url'])
                        <a
                            href="{{ $item['url'] }}"
                            class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-medium {{ $item['label'] === 'Dashboard' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="whitespace-nowrap rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                            {{ $item['label'] }}
                        </span>
                    @endif
                @endforeach
            </nav>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dashboard</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Overview of crime records and operational activity.</p>
                </div>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Case statistics">
                    @foreach ($statistics as $statistic)
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $statistic['label'] }}</p>
                                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ $statistic['value'] }}</p>
                                </div>
                                <span class="h-2.5 w-2.5 rounded-full {{ $statistic['accent'] }}"></span>
                            </div>
                            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">{{ $statistic['note'] }}</p>
                        </article>
                    @endforeach
                </section>

                <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Recent Cases</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Latest reported incidents</p>
                        </div>
                        <a href="{{ route('cases.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</a>
                    </div>

                    @if ($recentCases->isEmpty())
                        <p class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No cases are available to your role yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 font-medium">Case ID</th>
                                        <th scope="col" class="px-5 py-3 font-medium">Case Title / Type</th>
                                        <th scope="col" class="px-5 py-3 font-medium">Provisional classification</th>
                                        <th scope="col" class="px-5 py-3 font-medium">Location</th>
                                        <th scope="col" class="px-5 py-3 font-medium">Assigned Investigator</th>
                                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                                        <th scope="col" class="px-5 py-3 font-medium">Date Reported</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($recentCases as $case)
                                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900 dark:text-white">
                                                <a href="{{ route('cases.show', $case) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">#{{ $case->id }}</a>
                                            </td>
                                            <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                                                <span class="block">{{ $case->title }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $case->crime_type }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-400">
                                                @if ($case->crimeCategory)
                                                    <span class="block text-xs text-indigo-600 dark:text-indigo-400">Provisional classification</span>
                                                    <span class="mt-0.5 block text-xs">{{ collect($case->classification())->pluck('name')->implode(' → ') }}</span>
                                                @else
                                                    <span class="text-xs">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $case->location }}</td>
                                            <td class="whitespace-nowrap px-5 py-4 text-slate-600 dark:text-slate-400">{{ $case->investigator?->name ?? '—' }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex whitespace-nowrap rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ \Illuminate\Support\Str::headline($case->status->value) }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-slate-500 dark:text-slate-400">{{ $case->created_at?->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
