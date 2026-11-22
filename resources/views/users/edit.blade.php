<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit user') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Update account details and role. Account status is managed from the user list.') }}</p>

                    <dl class="mt-4 flex flex-wrap gap-6 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Account status') }}</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $user->is_active ? __('Active') : __('Deactivated') }}</dd>
                        </div>
                        @if ($user->officerProfile)
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Officer profile') }}</dt>
                                <dd class="mt-0.5">
                                    <a href="{{ route('officers.edit', $user->officerProfile) }}" class="text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Edit profile') }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if ($errors->any())
                        <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                            <ul class="list-disc space-y-1 ps-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('users.update', $user) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('New password (optional)')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->value }}" @selected(old('role', $user->role->value) === $roleOption->value)>{{ \Illuminate\Support\Str::headline($roleOption->value) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save changes') }}</x-primary-button>

                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
