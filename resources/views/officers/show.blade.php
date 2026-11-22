<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Officer profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $officerProfile->user?->name }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $officerProfile->user?->email }}</p>
                    </div>

                    <a href="{{ route('officers.edit', $officerProfile) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        {{ __('Edit profile') }}
                    </a>
                </div>

                <dl class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Badge number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->badge_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Rank') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->rank ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Department') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->department ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Police station') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->police_station ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Employment status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ \Illuminate\Support\Str::headline($officerProfile->employment_status->value) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Account role') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->user ? \Illuminate\Support\Str::headline($officerProfile->user->role->value) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Account status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $officerProfile->user?->is_active ? __('Active') : __('Deactivated') }}</dd>
                    </div>
                </dl>

                <div class="mt-8">
                    <a href="{{ route('officers.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        {{ __('Back to officers') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
