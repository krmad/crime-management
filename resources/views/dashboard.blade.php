<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">Dashboard</h2>
    </x-slot>

    @php
        $distribution = [
            ['label' => 'Open', 'value' => $statistics[1]['value'], 'bar' => 'bg-warning'],
            ['label' => 'Under Investigation', 'value' => $statistics[2]['value'], 'bar' => 'bg-brand'],
            ['label' => 'Judgment Recorded', 'value' => $statistics[3]['value'], 'bar' => 'bg-violet'],
            ['label' => 'Closed', 'value' => $statistics[4]['value'], 'bar' => 'bg-success'],
        ];

        $distributionTotal = array_sum(array_column($distribution, 'value'));
        $assignedRecentCases = $recentCases->filter(fn ($case) => $case->investigator !== null)->count();
    @endphp

    <x-page-container>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Welcome back, {{ Auth::user()->name }}</h1>
                <p class="mt-1 text-sm text-muted">Here is the current operational overview for cases visible to your role.</p>
            </div>

            @can('create', App\Models\CrimeCase::class)
                <x-link-button :href="route('cases.create')">
                    <x-icon name="plus" class="h-4 w-4" />
                    {{ __('New case report') }}
                </x-link-button>
            @endcan
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Case statistics">
            @foreach ($statistics as $index => $statistic)
                <x-stat-card
                    :label="$statistic['label']"
                    :value="$statistic['value']"
                    :note="$statistic['note']"
                    :tone="['brand', 'warning', 'brand', 'violet', 'success'][$index] ?? 'brand'"
                    :icon="['cases', 'clock', 'search', 'scale', 'check'][$index] ?? 'cases'"
                />
            @endforeach
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <x-card title="Recent Cases" subtitle="Latest reported incidents" :padded="false">
                <x-slot name="actions">
                    <x-link-button :href="route('cases.index')" tone="secondary">
                        {{ __('View all') }}
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </x-link-button>
                </x-slot>

                @if ($recentCases->isEmpty())
                    <div class="p-5">
                        <x-empty-state title="No recent cases" icon="cases">
                            <x-slot name="description">No cases are available to your role yet.</x-slot>
                        </x-empty-state>
                    </div>
                @else
                    <x-table min-width="wide">
                        <x-slot name="head">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Case ID</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Case Title / Type</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Provisional classification</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Location</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Assigned Investigator</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Status</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Date Reported</th>
                            </tr>
                        </x-slot>

                        @foreach ($recentCases as $case)
                            <tr class="cursor-pointer transition-colors hover:bg-canvas"
                                onclick="window.location.href='{{ route('cases.show', $case) }}'">
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-brand">
                                    <a href="{{ route('cases.show', $case) }}" class="cursor-pointer hover:underline">#{{ $case->id }}</a>
                                </td>
                                <td class="px-5 py-4 text-ink">
                                    <a href="{{ route('cases.show', $case) }}" class="block cursor-pointer font-medium hover:text-brand">{{ $case->title }}</a>
                                    <span class="mt-0.5 block text-xs text-muted">{{ $case->crime_type }}</span>
                                </td>
                                <td class="px-5 py-4 text-muted">
                                    @if ($case->crimeCategory)
                                        <span class="block text-xs font-semibold text-brand">Provisional classification</span>
                                        <span class="mt-0.5 block text-xs">{{ collect($case->classification())->pluck('name')->implode(' → ') }}</span>
                                    @else
                                        <span class="text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-muted">{{ $case->location }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $case->investigator?->name ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <x-status-badge :status="$case->status->value" />
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $case->created_at?->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            <div class="space-y-6">
                <x-card title="Case distribution" subtitle="Visible cases by current status">
                    <div class="space-y-4">
                        @foreach ($distribution as $item)
                            <div>
                                <div class="mb-1.5 flex items-center justify-between text-xs">
                                    <span class="font-medium text-ink">{{ $item['label'] }}</span>
                                    <span class="font-semibold text-muted">{{ $item['value'] }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-canvas">
                                    <div class="h-full rounded-full {{ $item['bar'] }}" style="width: {{ $distributionTotal > 0 ? round(($item['value'] / $distributionTotal) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Assignment overview" subtitle="From the latest {{ $recentCases->count() }} cases">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-brand-soft p-3">
                            <p class="text-2xl font-semibold text-brand">{{ $assignedRecentCases }}</p>
                            <p class="mt-1 text-xs text-muted">Assigned</p>
                        </div>
                        <div class="rounded-lg bg-canvas p-3">
                            <p class="text-2xl font-semibold text-ink">{{ $recentCases->count() - $assignedRecentCases }}</p>
                            <p class="mt-1 text-xs text-muted">Unassigned</p>
                        </div>
                    </div>
                </x-card>

                <x-card title="Quick actions" subtitle="Common operational tasks">
                    <div class="space-y-2">
                        @can('create', App\Models\CrimeCase::class)
                            <x-link-button :href="route('cases.create')" tone="secondary" class="w-full justify-start">
                                <x-icon name="plus" class="h-4 w-4" />
                                {{ __('Record a case') }}
                            </x-link-button>
                        @endcan

                        @can('viewAny', App\Models\CrimeCase::class)
                            <x-link-button :href="route('cases.index')" tone="secondary" class="w-full justify-start">
                                <x-icon name="cases" class="h-4 w-4" />
                                {{ __('Browse cases') }}
                            </x-link-button>
                            <x-link-button :href="route('reports.index')" tone="secondary" class="w-full justify-start">
                                <x-icon name="reports" class="h-4 w-4" />
                                {{ __('Open reports') }}
                            </x-link-button>
                        @endcan

                        @can('viewAny', App\Models\Evidence::class)
                            <x-link-button :href="route('evidence.index')" tone="secondary" class="w-full justify-start">
                                <x-icon name="evidence" class="h-4 w-4" />
                                {{ __('Evidence register') }}
                            </x-link-button>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </x-page-container>
</x-app-layout>
