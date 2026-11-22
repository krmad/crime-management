<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $statusMessages = [
                    'user-created' => 'User created.',
                    'user-updated' => 'User updated.',
                    'user-deactivated' => 'User deactivated.',
                    'user-activated' => 'User activated.',
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Accounts') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Manage login accounts and roles. Users linked to case records are never deleted.') }}</p>
                    </div>

                    <a href="{{ route('users.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        {{ __('Create user') }}
                    </a>
                </div>

                <form method="GET" action="{{ route('users.index') }}" class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Name or email')" />
                        <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$search" placeholder="Search users" />
                    </div>
                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach (\App\Enums\UserRole::cases() as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected($role === $roleOption->value)>{{ \Illuminate\Support\Str::headline($roleOption->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Account status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All statuses') }}</option>
                            <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected($status === 'inactive')>{{ __('Deactivated') }}</option>
                        </select>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                </form>

                @if ($users->isEmpty())
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No users match the selected filters.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Email') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Account status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Officer profile') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($users as $user)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::headline($user->role->value) }}</td>
                                        <td class="px-4 py-4">
                                            @if ($user->is_active)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Active') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-500/15 dark:text-red-300">{{ __('Deactivated') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">
                                            @if ($user->officerProfile)
                                                <a href="{{ route('officers.show', $user->officerProfile) }}" class="text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    {{ $user->officerProfile->badge_number ?? __('View profile') }}
                                                </a>
                                                <span class="mt-0.5 block text-xs">{{ \Illuminate\Support\Str::headline($user->officerProfile->employment_status->value) }}</span>
                                            @else
                                                <span class="text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a href="{{ route('users.edit', $user) }}" class="text-sm text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Edit') }}</a>

                                                @if ($user->is_active)
                                                    <form method="POST" action="{{ route('users.deactivate', $user) }}" onsubmit="return confirm('Deactivate this account? The user will no longer be able to log in.');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-sm text-red-600 underline hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">{{ __('Deactivate') }}</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('users.activate', $user) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-sm text-emerald-600 underline hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300">{{ __('Activate') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
