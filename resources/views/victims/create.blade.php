<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Add victim') }}</h2>
    </x-slot>

    <x-page-container>
        <x-card :title="__('Victim details')" :subtitle="__('Create a standalone profile, then link it to a case from the case details page.')">
            <form method="POST" action="{{ route('victims.store') }}" class="mx-auto max-w-3xl space-y-6">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="full_name" :value="__('Full name')" />
                        <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name')" required autofocus />
                        <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" />
                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="gender" :value="__('Gender')" />
                        <x-text-input id="gender" name="gender" type="text" class="mt-1 block w-full" :value="old('gender')" />
                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="address" :value="__('Address')" />
                        <x-textarea-input id="address" name="address" rows="3">{{ old('address') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
                    <x-primary-button>{{ __('Create victim') }}</x-primary-button>
                    <x-link-button :href="route('victims.index')" tone="secondary">{{ __('Cancel') }}</x-link-button>
                </div>
            </form>
        </x-card>
    </x-page-container>
</x-app-layout>
