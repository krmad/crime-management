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
    @php
        $navigation = [
            'Dashboard',
            'Crime Reports',
            'Cases',
            'Officers',
            'Suspects',
            'Victims',
            'Evidence',
            'Reports',
            'User Management',
        ];

        $statistics = [
            ['label' => 'Total Cases', 'value' => '128', 'note' => 'All recorded cases', 'accent' => 'bg-indigo-500'],
            ['label' => 'Open Cases', 'value' => '42', 'note' => 'Awaiting action', 'accent' => 'bg-amber-500'],
            ['label' => 'Closed Cases', 'value' => '86', 'note' => 'Resolved cases', 'accent' => 'bg-emerald-500'],
            ['label' => 'Total Officers', 'value' => '24', 'note' => 'Active personnel', 'accent' => 'bg-sky-500'],
        ];

        $cases = [
            ['id' => 'CR-2026-0142', 'type' => 'Burglary', 'location' => 'Downtown District', 'officer' => 'Insp. Maria Santos', 'status' => 'Open', 'statusClass' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300', 'date' => 'Nov 18, 2026'],
            ['id' => 'CR-2026-0141', 'type' => 'Assault', 'location' => 'Westside Avenue', 'officer' => 'PO. Daniel Reyes', 'status' => 'Under Investigation', 'statusClass' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300', 'date' => 'Nov 17, 2026'],
            ['id' => 'CR-2026-0138', 'type' => 'Vehicle Theft', 'location' => 'North Harbor', 'officer' => 'Insp. Lina Cruz', 'status' => 'Closed', 'statusClass' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300', 'date' => 'Nov 15, 2026'],
            ['id' => 'CR-2026-0135', 'type' => 'Fraud', 'location' => 'Central Business District', 'officer' => 'PO. Ana Villanueva', 'status' => 'Under Investigation', 'statusClass' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300', 'date' => 'Nov 12, 2026'],
            ['id' => 'CR-2026-0130', 'type' => 'Vandalism', 'location' => 'Eastside Park', 'officer' => 'PO. Miguel Torres', 'status' => 'Closed', 'statusClass' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300', 'date' => 'Nov 09, 2026'],
        ];
    @endphp

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
                    <a
                        href="#"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ $item === 'Dashboard' ? 'bg-indigo-600 text-white' : 'hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linejoin="round" d="M3.75 6.75h4.5v4.5h-4.5zM15.75 6.75h4.5v4.5h-4.5zM3.75 15h4.5v4.5h-4.5zM15.75 15h4.5v4.5h-4.5z" />
                        </svg>
                        {{ $item }}
                    </a>
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
                    <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 sm:inline dark:bg-slate-800 dark:text-slate-300">Admin</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">AD</span>
                </div>
            </header>

            <nav class="flex gap-2 overflow-x-auto border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 lg:hidden">
                @foreach ($navigation as $item)
                    <a
                        href="#"
                        class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-medium {{ $item === 'Dashboard' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}"
                    >
                        {{ $item }}
                    </a>
                @endforeach
            </nav>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dashboard</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Overview of crime records and operational activity.</p>
                </div>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Case statistics">
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
                        <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-medium">Case ID</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Crime Type</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Location</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Assigned Officer</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Status</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Date Reported</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($cases as $case)
                                    <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900 dark:text-white">{{ $case['id'] }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-700 dark:text-slate-300">{{ $case['type'] }}</td>
                                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $case['location'] }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600 dark:text-slate-400">{{ $case['officer'] }}</td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $case['statusClass'] }}">{{ $case['status'] }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-500 dark:text-slate-400">{{ $case['date'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
