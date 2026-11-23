<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Victims') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $statusMessages = [
                    'victim-created' => 'Victim created.',
                    'victim-updated' => 'Victim updated.',
                ];
            @endphp

            @if (isset($statusMessages[session('status')]))
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __($statusMessages[session('status')]) }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Victim profiles') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Only victims linked to cases you can access are listed.') }}</p>
                    </div>

                    @can('create', App\Models\Victim::class)
                        <a href="{{ route('victims.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            {{ __('Add victim') }}
                        </a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('victims.index') }}" class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Name')" />
                        <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$search" placeholder="Search victims" />
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                </form>

                @if ($victims->isEmpty())
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No victim profiles match the selected filters.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Date of birth') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Phone') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Visible cases') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($victims as $victim)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $victim->full_name }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $victim->date_of_birth?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $victim->phone ?? '—' }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $victim->cases_count }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap gap-3">
                                                <a href="{{ route('victims.show', $victim) }}" class="text-sm text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('View') }}</a>
                                                @can('update', $victim)
                                                    <a href="{{ route('victims.edit', $victim) }}" class="text-sm text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Edit') }}</a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $victims->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
