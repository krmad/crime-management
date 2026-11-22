<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit officer profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $officerProfile->user?->name }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Update employment details. Login account status is managed in User Management.') }}</p>

                    @if ($errors->any())
                        <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                            <ul class="list-disc space-y-1 ps-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('officers.update', $officerProfile) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="badge_number" :value="__('Badge number')" />
                            <x-text-input id="badge_number" name="badge_number" type="text" class="mt-1 block w-full" :value="old('badge_number', $officerProfile->badge_number)" />
                            <x-input-error :messages="$errors->get('badge_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="rank" :value="__('Rank')" />
                            <x-text-input id="rank" name="rank" type="text" class="mt-1 block w-full" :value="old('rank', $officerProfile->rank)" />
                            <x-input-error :messages="$errors->get('rank')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="department" :value="__('Department')" />
                            <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $officerProfile->department)" />
                            <x-input-error :messages="$errors->get('department')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="police_station" :value="__('Police station')" />
                            <x-text-input id="police_station" name="police_station" type="text" class="mt-1 block w-full" :value="old('police_station', $officerProfile->police_station)" />
                            <x-input-error :messages="$errors->get('police_station')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $officerProfile->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="employment_status" :value="__('Employment status')" />
                            <select id="employment_status" name="employment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                @foreach ($employmentStatuses as $employmentStatusOption)
                                    <option value="{{ $employmentStatusOption->value }}" @selected(old('employment_status', $officerProfile->employment_status->value) === $employmentStatusOption->value)>{{ \Illuminate\Support\Str::headline($employmentStatusOption->value) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save changes') }}</x-primary-button>

                            <a href="{{ route('officers.show', $officerProfile) }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
