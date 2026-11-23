<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Create user') }}</h2>
    </x-slot>

    <x-page-container>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Create user') }}</h1>
                <p class="mt-1 text-sm text-muted">{{ __('Set a temporary password and share it securely with the user.') }}</p>
            </div>

            <x-link-button :href="route('users.index')" tone="secondary">
                <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
                {{ __('Back to users') }}
            </x-link-button>
        </div>

        <x-card title="{{ __('New account') }}" subtitle="{{ __('Account details and optional officer profile.') }}">
            @if ($errors->any())
                <x-alert tone="danger" class="mb-6">
                    <ul class="list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                @csrf

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Temporary password')" />
                        <x-text-input id="password" name="password" type="password" autocomplete="new-password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <x-select-input id="role" name="role" required>
                            <option value="">{{ __('Select a role') }}</option>
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected(old('role') === $roleOption->value)>{{ \Illuminate\Support\Str::headline($roleOption->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                </div>

                <div class="border-t border-line pt-6">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Officer profile (optional)') }}</h4>
                    <p class="mt-1 text-xs text-muted">{{ __('Applied only to police officer and investigator accounts. A profile can also be added later from Officers Management.') }}</p>

                    <div class="mt-4 grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="badge_number" :value="__('Badge number')" />
                            <x-text-input id="badge_number" name="badge_number" type="text" :value="old('badge_number')" />
                            <x-input-error :messages="$errors->get('badge_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="rank" :value="__('Rank')" />
                            <x-text-input id="rank" name="rank" type="text" :value="old('rank')" />
                            <x-input-error :messages="$errors->get('rank')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="department" :value="__('Department')" />
                            <x-text-input id="department" name="department" type="text" :value="old('department')" />
                            <x-input-error :messages="$errors->get('department')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="police_station" :value="__('Police station')" />
                            <x-text-input id="police_station" name="police_station" type="text" :value="old('police_station')" />
                            <x-input-error :messages="$errors->get('police_station')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" :value="old('phone')" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="employment_status" :value="__('Employment status')" />
                            <x-select-input id="employment_status" name="employment_status">
                                @foreach (\App\Enums\EmploymentStatus::cases() as $employmentStatus)
                                    <option value="{{ $employmentStatus->value }}" @selected(old('employment_status', \App\Enums\EmploymentStatus::Active->value) === $employmentStatus->value)>{{ \Illuminate\Support\Str::headline($employmentStatus->value) }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line pt-6">
                    <x-primary-button>{{ __('Create user') }}</x-primary-button>

                    <x-link-button :href="route('users.index')" tone="secondary">
                        {{ __('Cancel') }}
                    </x-link-button>
                </div>
            </form>
        </x-card>
    </x-page-container>
</x-app-layout>
