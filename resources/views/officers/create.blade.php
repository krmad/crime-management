<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Add officer profile') }}</h2>
    </x-slot>

    <x-page-container width="6xl">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Link a profile to an existing account') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('Only police officer and investigator accounts without a profile can be selected. No duplicate account is created.') }}</p>
        </div>

        @if ($errors->any())
            <x-alert tone="danger">
                <ul class="list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <x-card>
            @if ($eligibleUsers->isEmpty())
                <x-empty-state title="{{ __('No eligible accounts are available.') }}" icon="users">
                    <x-slot name="description">
                        <a href="{{ route('users.create') }}" class="font-semibold text-brand hover:underline">{{ __('Create a user first.') }}</a>
                    </x-slot>
                </x-empty-state>
            @else
                <form method="POST" action="{{ route('officers.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="user_id" :value="__('User account')" />
                            <x-select-input id="user_id" name="user_id" required>
                                <option value="">{{ __('Select an account') }}</option>
                                @foreach ($eligibleUsers as $eligibleUser)
                                    <option value="{{ $eligibleUser->id }}" @selected(old('user_id') == $eligibleUser->id)>{{ $eligibleUser->name }} — {{ \Illuminate\Support\Str::headline($eligibleUser->role->value) }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('user_id')" />
                        </div>

                        <div>
                            <x-input-label for="employment_status" :value="__('Employment status')" />
                            <x-select-input id="employment_status" name="employment_status" required>
                                @foreach ($employmentStatuses as $employmentStatusOption)
                                    <option value="{{ $employmentStatusOption->value }}" @selected(old('employment_status', \App\Enums\EmploymentStatus::Active->value) === $employmentStatusOption->value)>{{ \Illuminate\Support\Str::headline($employmentStatusOption->value) }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('employment_status')" />
                        </div>

                        <div>
                            <x-input-label for="badge_number" :value="__('Badge number')" />
                            <x-text-input id="badge_number" name="badge_number" type="text" :value="old('badge_number')" />
                            <x-input-error :messages="$errors->get('badge_number')" />
                        </div>

                        <div>
                            <x-input-label for="rank" :value="__('Rank')" />
                            <x-text-input id="rank" name="rank" type="text" :value="old('rank')" />
                            <x-input-error :messages="$errors->get('rank')" />
                        </div>

                        <div>
                            <x-input-label for="department" :value="__('Department')" />
                            <x-text-input id="department" name="department" type="text" :value="old('department')" />
                            <x-input-error :messages="$errors->get('department')" />
                        </div>

                        <div>
                            <x-input-label for="police_station" :value="__('Police station')" />
                            <x-text-input id="police_station" name="police_station" type="text" :value="old('police_station')" />
                            <x-input-error :messages="$errors->get('police_station')" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" :value="old('phone')" />
                            <x-input-error :messages="$errors->get('phone')" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <x-primary-button>{{ __('Create profile') }}</x-primary-button>

                        <x-link-button :href="route('officers.index')" tone="secondary">
                            {{ __('Cancel') }}
                        </x-link-button>
                    </div>
                </form>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
