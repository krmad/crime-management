<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Cases') }}</h2>
    </x-slot>

    <x-page-container>
        @if (session('status') === 'case-created')
            <x-alert tone="success">{{ __('Case created.') }}</x-alert>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Cases') }}</h1>
                <p class="mt-1 text-sm text-muted">{{ __('Cases visible to your role.') }}</p>
            </div>
        </div>

        <x-card :title="__('Case records')" :padded="false">
            <x-slot name="actions">
                @can('create', App\Models\CrimeCase::class)
                    <x-link-button :href="route('cases.create')">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Create case') }}
                    </x-link-button>
                @endcan
            </x-slot>

            @if ($cases->isEmpty())
                <div class="p-5">
                    <x-empty-state :title="__('No cases are available.')" icon="cases" />
                </div>
            @else
                <x-table min-width="wide">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Title') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Crime type') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Provisional classification') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Location') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Incident date') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Creator') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Investigator') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($cases as $case)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="px-5 py-4">
                                <a href="{{ route('cases.show', $case) }}" class="font-semibold text-ink transition hover:text-brand">{{ $case->title }}</a>
                            </td>
                            <td class="px-5 py-4 text-ink">{{ $case->crime_type }}</td>
                            <td class="px-5 py-4 text-muted">
                                @if ($case->crimeCategory)
                                    <span class="block text-xs font-semibold text-brand">{{ __('Provisional classification') }}</span>
                                    <span class="mt-1 block text-xs">{{ collect($case->classification())->pluck('name')->implode(' → ') }}</span>
                                @else
                                    <span class="text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $case->location }}</td>
                            <td class="px-5 py-4">
                                <x-status-badge :status="$case->status->value" />
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $case->incident_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $case->creator?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $case->investigator?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <a href="{{ route('cases.show', $case) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand transition hover:text-brand-dark">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    {{ __('View Details') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="border-t border-line bg-surface px-5 py-4">
                    {{ $cases->links() }}
                </div>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
