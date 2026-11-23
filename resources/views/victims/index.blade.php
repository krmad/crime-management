<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Victims') }}</h2>
    </x-slot>

    @php
        $statusMessages = [
            'victim-created' => 'Victim created.',
            'victim-updated' => 'Victim updated.',
        ];
    @endphp

    <x-page-container>
        @if (isset($statusMessages[session('status')]))
            <x-alert tone="success">{{ __($statusMessages[session('status')]) }}</x-alert>
        @endif

        <x-card :title="__('Victim profiles')" :subtitle="__('Only victims linked to cases you can access are listed.')" :padded="false">
            <x-slot name="actions">
                @can('create', App\Models\Victim::class)
                    <x-link-button :href="route('victims.create')">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Add victim') }}
                    </x-link-button>
                @endcan
            </x-slot>

            <form method="GET" action="{{ route('victims.index') }}" class="grid gap-4 border-b border-line p-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <div>
                    <x-input-label for="search" :value="__('Name')" />
                    <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$search" placeholder="Search victims" />
                </div>
                <x-primary-button>{{ __('Filter') }}</x-primary-button>
            </form>

            @if ($victims->isEmpty())
                <div class="p-5">
                    <x-empty-state :title="__('No victim profiles match the selected filters.')" icon="victim" />
                </div>
            @else
                <x-table min-width="wide">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Name') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Date of birth') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Phone') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Visible cases') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($victims as $victim)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="px-5 py-4 font-semibold text-ink">{{ $victim->full_name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $victim->date_of_birth?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-4 text-muted">{{ $victim->phone ?? '—' }}</td>
                            <td class="px-5 py-4 text-muted">{{ $victim->cases_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    <a href="{{ route('victims.show', $victim) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand hover:text-brand-dark hover:underline">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        {{ __('View') }}
                                    </a>
                                    @can('update', $victim)
                                        <a href="{{ route('victims.edit', $victim) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand hover:text-brand-dark hover:underline">
                                            <x-icon name="file" class="h-4 w-4" />
                                            {{ __('Edit') }}
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="border-t border-line bg-canvas px-5 py-3">
                    {{ $victims->links() }}
                </div>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
