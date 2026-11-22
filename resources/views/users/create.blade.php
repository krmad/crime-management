<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create user') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('New account') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Set a temporary password and share it securely with the user.') }}</p>

                    @if ($errors->any())
                        <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                            <ul class="list-disc space-y-1 ps-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('users.store') }}" class="mt-6 space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Temporary password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                <option value="">{{ __('Select a role') }}</option>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->value }}" @selected(old('role') === $roleOption->value)>{{ \Illuminate\Support\Str::headline($roleOption->value) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Officer profile (optional)') }}</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Applied only to police officer and investigator accounts. A profile can also be added later from Officers Management.') }}</p>

                            <div class="mt-4 space-y-6">
                                <div>
                                    <x-input-label for="badge_number" :value="__('Badge number')" />
                                    <x-text-input id="badge_number" name="badge_number" type="text" class="mt-1 block w-full" :value="old('badge_number')" />
                                    <x-input-error :messages="$errors->get('badge_number')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="rank" :value="__('Rank')" />
                                    <x-text-input id="rank" name="rank" type="text" class="mt-1 block w-full" :value="old('rank')" />
                                    <x-input-error :messages="$errors->get('rank')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="department" :value="__('Department')" />
                                    <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department')" />
                                    <x-input-error :messages="$errors->get('department')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="police_station" :value="__('Police station')" />
                                    <x-text-input id="police_station" name="police_station" type="text" class="mt-1 block w-full" :value="old('police_station')" />
                                    <x-input-error :messages="$errors->get('police_station')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="phone" :value="__('Phone')" />
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="employment_status" :value="__('Employment status')" />
                                    <select id="employment_status" name="employment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        @foreach (\App\Enums\EmploymentStatus::cases() as $employmentStatus)
                                            <option value="{{ $employmentStatus->value }}" @selected(old('employment_status', \App\Enums\EmploymentStatus::Active->value) === $employmentStatus->value)>{{ \Illuminate\Support\Str::headline($employmentStatus->value) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Create user') }}</x-primary-button>

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
