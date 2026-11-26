<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Edit user') }}</h2>
    </x-slot>

    <x-page-container>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-muted">{{ __('Update account details and role. Account status is managed from the user list.') }}</p>
            </div>

            <x-link-button :href="route('users.index')" tone="secondary">
                <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
                {{ __('Back to users') }}
            </x-link-button>
        </div>

        <x-card title="{{ __('Account details') }}" subtitle="{{ __('Name, email, password, and role.') }}">
            <div class="mb-6 flex flex-wrap items-center gap-6 rounded-lg bg-canvas p-4 text-sm">
                <div>
                    <p class="font-semibold text-muted">{{ __('Account status') }}</p>
                    <div class="mt-1">
                        @if ($user->is_active)
                            <span class="inline-flex whitespace-nowrap rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success-ink dark:text-success">{{ $user->is_active ? __('Active') : __('Deactivated') }}</span>
                        @else
                            <span class="inline-flex whitespace-nowrap rounded-full bg-danger-soft px-2.5 py-1 text-xs font-semibold text-danger-ink dark:text-danger">{{ $user->is_active ? __('Active') : __('Deactivated') }}</span>
                        @endif
                    </div>
                </div>
                @if ($user->officerProfile)
                    <div>
                        <p class="font-semibold text-muted">{{ __('Officer profile') }}</p>
                        <a href="{{ route('officers.edit', $user->officerProfile) }}" class="mt-1 inline-block text-sm font-semibold text-brand hover:underline">{{ __('Edit profile') }}</a>
                    </div>
                @endif
            </div>

            @if ($errors->any())
                <x-alert tone="danger" class="mb-6">
                    <ul class="list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('New password (optional)')" />
                        <x-text-input id="password" name="password" type="password" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <x-select-input id="role" name="role" required>
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected(old('role', $user->role->value) === $roleOption->value)>{{ __(\Illuminate\Support\Str::headline($roleOption->value)) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line pt-6">
                    <x-link-button :href="route('users.index')" tone="secondary">
                        {{ __('Cancel') }}
                    </x-link-button>

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </div>
            </form>
        </x-card>
    </x-page-container>
</x-app-layout>
