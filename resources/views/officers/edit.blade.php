<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Edit officer profile') }}</h2>
    </x-slot>

    <x-page-container width="6xl">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $officerProfile->user?->name }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('Update employment details. Login account status is managed in User Management.') }}</p>
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
            <form method="POST" action="{{ route('officers.update', $officerProfile) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="badge_number" :value="__('Badge number')" />
                        <x-text-input id="badge_number" name="badge_number" type="text" :value="old('badge_number', $officerProfile->badge_number)" />
                        <x-input-error :messages="$errors->get('badge_number')" />
                    </div>

                    <div>
                        <x-input-label for="rank" :value="__('Rank')" />
                        <x-text-input id="rank" name="rank" type="text" :value="old('rank', $officerProfile->rank)" />
                        <x-input-error :messages="$errors->get('rank')" />
                    </div>

                    <div>
                        <x-input-label for="department" :value="__('Department')" />
                        <x-text-input id="department" name="department" type="text" :value="old('department', $officerProfile->department)" />
                        <x-input-error :messages="$errors->get('department')" />
                    </div>

                    <div>
                        <x-input-label for="police_station" :value="__('Police station')" />
                        <x-text-input id="police_station" name="police_station" type="text" :value="old('police_station', $officerProfile->police_station)" />
                        <x-input-error :messages="$errors->get('police_station')" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" :value="old('phone', $officerProfile->phone)" />
                        <x-input-error :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <x-input-label for="employment_status" :value="__('Employment status')" />
                        <x-select-input id="employment_status" name="employment_status" required>
                            @foreach ($employmentStatuses as $employmentStatusOption)
                                <option value="{{ $employmentStatusOption->value }}" @selected(old('employment_status', $officerProfile->employment_status->value) === $employmentStatusOption->value)>{{ \Illuminate\Support\Str::headline($employmentStatusOption->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('employment_status')" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>

                    <x-link-button :href="route('officers.show', $officerProfile)" tone="secondary">
                        {{ __('Cancel') }}
                    </x-link-button>
                </div>
            </form>
        </x-card>
    </x-page-container>
</x-app-layout>
