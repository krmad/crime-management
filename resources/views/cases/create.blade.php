<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create case') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('New case report') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Record the initial report details. Assignment and investigation happen later.') }}</p>

                    <form method="POST" action="{{ route('cases.store') }}"
                        x-data="{
                            titleType: @js(old('case_title_type', '')),
                            customTitleTypeValue: @js($customTitleTypeValue),
                            categories: {{ \Illuminate\Support\Js::from($categories) }},
                            level1: @js(old('level_1_category_id', '')),
                            level2: @js(old('level_2_category_id', '')),
                            level3: @js(old('crime_category_id', '')),
                            get isCustomTitle() { return String(this.titleType) === String(this.customTitleTypeValue); },
                            get level2Options() {
                                return this.categories.find((category) => String(category.id) === String(this.level1))?.children || [];
                            },
                            get level3Options() {
                                return this.level2Options.find((category) => String(category.id) === String(this.level2))?.children || [];
                            },
                            level1Changed() { this.level2 = ''; this.level3 = ''; },
                            level2Changed() { this.level3 = ''; },
                        }"
                        class="mt-6 space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="case_title_type" :value="__('Case title / type')" />
                            <select id="case_title_type" name="case_title_type" x-model="titleType" required autofocus class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">
                                <option value="">{{ __('Select a case title / type') }}</option>
                                @foreach ($titleTypes as $titleType)
                                    <option value="{{ $titleType['value'] }}" @selected(old('case_title_type') === $titleType['value'])>{{ $titleType['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('case_title_type')" class="mt-2" />
                        </div>

                        <div x-show="isCustomTitle" style="display: none">
                            <x-input-label for="custom_crime_type" :value="__('Custom crime type')" />
                            <x-text-input id="custom_crime_type" name="custom_crime_type" type="text" class="mt-1 block w-full" :value="old('custom_crime_type')" maxlength="255" x-bind:required="isCustomTitle" x-bind:disabled="!isCustomTitle" />
                            <x-input-error :messages="$errors->get('custom_crime_type')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="level_1_category_id" :value="__('Level 1 category')" />
                            <select id="level_1_category_id" name="level_1_category_id" x-model="level1" x-on:change="level1Changed()" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">
                                <option value="">{{ __('Select a Level 1 category') }}</option>
                                @foreach ($categories as $rootCategory)
                                    <option value="{{ $rootCategory['id'] }}" @selected((string) old('level_1_category_id') === (string) $rootCategory['id'])>{{ $rootCategory['name'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('level_1_category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="level_2_category_id" :value="__('Level 2 category')" />
                            <select id="level_2_category_id" name="level_2_category_id" x-model="level2" x-on:change="level2Changed()" x-bind:disabled="level2Options.length === 0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 disabled:opacity-60">
                                <option value="">{{ __('Select a Level 2 category') }}</option>
                                <template x-for="category in level2Options" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('level_2_category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="crime_category_id" :value="__('Level 3 category')" />
                            <select id="crime_category_id" name="crime_category_id" x-model="level3" x-bind:disabled="level3Options.length === 0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 disabled:opacity-60">
                                <option value="">{{ __('Select a Level 3 category') }}</option>
                                <template x-for="category in level3Options" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('crime_category_id')" class="mt-2" />
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Categories are provisional, based on publicly available Ethiopian legal and Oromia Police sources. They are not officially approved by Oromia Regional Police.') }}</p>

                        <div>
                            <x-input-label for="location" :value="__('Location')" />
                            <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location')" required />
                            <x-input-error :messages="$errors->get('location')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" required>{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="incident_date" :value="__('Incident date (optional)')" />
                            <x-text-input id="incident_date" name="incident_date" type="datetime-local" class="mt-1 block w-full" :value="old('incident_date')" />
                            <x-input-error :messages="$errors->get('incident_date')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Create case') }}</x-primary-button>

                            <a href="{{ route('cases.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
