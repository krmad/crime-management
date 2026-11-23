<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit suspect') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $suspect->full_name }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Update the details shared across this suspect’s visible case links.') }}</p>

                    <form method="POST" action="{{ route('suspects.update', $suspect) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="full_name" :value="__('Full name')" />
                            <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name', $suspect->full_name)" required autofocus />
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="alias" :value="__('Alias')" />
                            <x-text-input id="alias" name="alias" type="text" class="mt-1 block w-full" :value="old('alias', $suspect->alias)" />
                            <x-input-error :messages="$errors->get('alias')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $suspect->date_of_birth?->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gender" :value="__('Gender')" />
                            <x-text-input id="gender" name="gender" type="text" class="mt-1 block w-full" :value="old('gender', $suspect->gender)" />
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="address" :value="__('Address')" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('address', $suspect->address) }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $suspect->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save changes') }}</x-primary-button>

                            <a href="{{ route('suspects.show', $suspect) }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
