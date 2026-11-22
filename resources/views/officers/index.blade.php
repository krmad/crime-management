<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Officers Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $statusMessages = [
                    'officer-created' => 'Officer profile created.',
                    'officer-updated' => 'Officer profile updated.',
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Officer profiles') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Employment status is separate from login account status.') }}</p>
                    </div>

                    <a href="{{ route('officers.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        {{ __('Add officer profile') }}
                    </a>
                </div>

                <form method="GET" action="{{ route('officers.index') }}" class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Name or badge number')" />
                        <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$search" placeholder="Search officers" />
                    </div>
                    <div>
                        <x-input-label for="rank" :value="__('Rank')" />
                        <select id="rank" name="rank" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All ranks') }}</option>
                            @foreach ($ranks as $rankOption)
                                <option value="{{ $rankOption }}" @selected($rank === $rankOption)>{{ $rankOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="employment_status" :value="__('Employment status')" />
                        <select id="employment_status" name="employment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Enums\EmploymentStatus::cases() as $employmentStatusOption)
                                <option value="{{ $employmentStatusOption->value }}" @selected($employmentStatus === $employmentStatusOption->value)>{{ \Illuminate\Support\Str::headline($employmentStatusOption->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                </form>

                @if ($officers->isEmpty())
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No officer profiles match the selected filters.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Officer') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Badge number') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Rank') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Station') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Employment status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Account status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($officers as $officerProfile)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $officerProfile->user?->name }}</td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $officerProfile->badge_number ?? '—' }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $officerProfile->rank ?? '—' }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $officerProfile->police_station ?? '—' }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ \Illuminate\Support\Str::headline($officerProfile->employment_status->value) }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $officerProfile->user?->is_active ? __('Active') : __('Deactivated') }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap gap-3">
                                                <a href="{{ route('officers.show', $officerProfile) }}" class="text-sm text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('View') }}</a>
                                                <a href="{{ route('officers.edit', $officerProfile) }}" class="text-sm text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Edit') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $officers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
