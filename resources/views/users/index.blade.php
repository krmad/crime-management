<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('User Management') }}</h2>
    </x-slot>

    @php
        $statusMessages = [
            'user-created' => 'User created.',
            'user-updated' => 'User updated.',
            'user-deactivated' => 'User deactivated.',
            'user-activated' => 'User activated.',
        ];
    @endphp

    <x-page-container>
        @if (isset($statusMessages[session('status')]))
            <x-alert tone="success">{{ __($statusMessages[session('status')]) }}</x-alert>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Accounts') }}</h1>
                <p class="mt-1 text-sm text-muted">{{ __('Manage login accounts and roles. Users linked to case records are never deleted.') }}</p>
            </div>

            <x-link-button :href="route('users.create')">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Create user') }}
            </x-link-button>
        </div>

        <x-card :padded="false">
            <div class="p-5">
                <form method="GET" action="{{ route('users.index') }}" class="grid gap-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Name or email')" />
                        <x-text-input id="search" name="search" type="search" :value="$search" placeholder="Search users" />
                    </div>
                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <x-select-input id="role" name="role">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach (\App\Enums\UserRole::cases() as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected($role === $roleOption->value)>{{ \Illuminate\Support\Str::headline($roleOption->value) }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Account status')" />
                        <x-select-input id="status" name="status">
                            <option value="">{{ __('All statuses') }}</option>
                            <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected($status === 'inactive')>{{ __('Deactivated') }}</option>
                        </x-select-input>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                </form>
            </div>

            @if ($users->isEmpty())
                <div class="p-5">
                    <x-empty-state title="{{ __('No users match the selected filters.') }}" icon="users" />
                </div>
            @else
                <x-table min-width="wide">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Name') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Email') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Role') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Account status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Officer profile') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($users as $user)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="px-5 py-4 font-semibold text-ink">{{ $user->name }}</td>
                            <td class="px-5 py-4 text-muted">{{ $user->email }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand-ink dark:text-brand">{{ \Illuminate\Support\Str::headline($user->role->value) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($user->is_active)
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success-ink dark:text-success">{{ __('Active') }}</span>
                                @else
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-danger-soft px-2.5 py-1 text-xs font-semibold text-danger-ink dark:text-danger">{{ __('Deactivated') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-muted">
                                @if ($user->officerProfile)
                                    <a href="{{ route('officers.show', $user->officerProfile) }}" class="font-semibold text-brand hover:underline">
                                        {{ $user->officerProfile->badge_number ?? __('View profile') }}
                                    </a>
                                    <span class="mt-0.5 block text-xs">{{ \Illuminate\Support\Str::headline($user->officerProfile->employment_status->value) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-4">
                                    <a href="{{ route('users.edit', $user) }}" class="text-sm font-semibold text-brand hover:underline">{{ __('Edit') }}</a>

                                    @if ($user->is_active)
                                        <form method="POST" action="{{ route('users.deactivate', $user) }}" onsubmit="return confirm('Deactivate this account? The user will no longer be able to log in.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-semibold text-danger hover:underline">{{ __('Deactivate') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('users.activate', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-semibold text-success hover:underline">{{ __('Activate') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="p-5">
                    {{ $users->links() }}
                </div>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
