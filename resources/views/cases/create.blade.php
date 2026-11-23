<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">Crime Reports</h2>
    </x-slot>

    <x-page-container width="6xl">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('New case report') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('Record the initial report details. Assignment and investigation happen later.') }}</p>
        </div>

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
            class="space-y-6">
            @csrf

            <x-card title="Report details" subtitle="Classification and incident information">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <x-input-label for="case_title_type" :value="__('Case title / type')" />
                        <x-select-input id="case_title_type" name="case_title_type" x-model="titleType" required autofocus>
                            <option value="">{{ __('Select a case title / type') }}</option>
                            @foreach ($titleTypes as $titleType)
                                <option value="{{ $titleType['value'] }}" @selected(old('case_title_type') === $titleType['value'])>{{ $titleType['label'] }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('case_title_type')" />
                    </div>

                    <div x-show="isCustomTitle" style="display: none">
                        <x-input-label for="custom_crime_type" :value="__('Custom crime type')" />
                        <x-text-input id="custom_crime_type" name="custom_crime_type" type="text" :value="old('custom_crime_type')" maxlength="255" x-bind:required="isCustomTitle" x-bind:disabled="!isCustomTitle" />
                        <x-input-error :messages="$errors->get('custom_crime_type')" />
                    </div>

                    <div>
                        <x-input-label for="location">
                            {{ __('Location') }} <span class="text-danger" aria-hidden="true">*</span>
                        </x-input-label>
                        <x-text-input id="location" name="location" type="text" :value="old('location')" maxlength="255" required />
                        <x-input-error :messages="$errors->get('location')" />
                    </div>

                    <div>
                        <x-input-label for="incident_date" :value="__('Incident date (optional)')" />
                        <x-text-input id="incident_date" name="incident_date" type="datetime-local" :value="old('incident_date')" />
                        <x-input-error :messages="$errors->get('incident_date')" />
                    </div>

                    <div class="lg:col-span-2">
                        <x-input-label for="description" :value="__('Description')" />
                        <x-textarea-input id="description" name="description" rows="5" required>{{ old('description') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('description')" />
                    </div>
                </div>
            </x-card>

            <x-card title="Known suspect" subtitle="Record the suspect name when it is known.">
                <p class="mb-5 rounded-lg border border-brand/20 bg-brand-soft/60 px-4 py-3 text-sm text-ink">
                    {{ __('Enter the actual full name when the suspect is known. Leave blank or enter None if the suspect is unknown; an alias cannot be entered without a known suspect.') }}
                </p>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <x-input-label for="suspect_name">
                            {{ __('Suspect Full Name (optional)') }}
                        </x-input-label>
                        <x-text-input id="suspect_name" name="suspect_name" type="text" :value="old('suspect_name')" maxlength="255" />
                        <x-input-error :messages="$errors->get('suspect_name')" />
                    </div>

                    <div>
                        <x-input-label for="suspect_alias" :value="__('Alias (optional)')" />
                        <x-text-input id="suspect_alias" name="suspect_alias" type="text" :value="old('suspect_alias')" maxlength="255" />
                        <x-input-error :messages="$errors->get('suspect_alias')" />
                    </div>
                </div>
            </x-card>

            <x-card title="Provisional classification" subtitle="Select the active category hierarchy that best matches the report">
                <div class="grid gap-5 lg:grid-cols-3">
                    <div>
                        <x-input-label for="level_1_category_id" :value="__('Level 1 category')" />
                        <x-select-input id="level_1_category_id" name="level_1_category_id" x-model="level1" x-on:change="level1Changed()" required>
                            <option value="">{{ __('Select a Level 1 category') }}</option>
                            @foreach ($categories as $rootCategory)
                                <option value="{{ $rootCategory['id'] }}" @selected((string) old('level_1_category_id') === (string) $rootCategory['id'])>{{ $rootCategory['name'] }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('level_1_category_id')" />
                    </div>

                    <div>
                        <x-input-label for="level_2_category_id" :value="__('Level 2 category')" />
                        <x-select-input id="level_2_category_id" name="level_2_category_id" x-model="level2" x-on:change="level2Changed()" x-bind:disabled="level2Options.length === 0" required>
                            <option value="">{{ __('Select a Level 2 category') }}</option>
                            <template x-for="category in level2Options" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('level_2_category_id')" />
                    </div>

                    <div>
                        <x-input-label for="crime_category_id" :value="__('Level 3 category')" />
                        <x-select-input id="crime_category_id" name="crime_category_id" x-model="level3" x-bind:disabled="level3Options.length === 0" required>
                            <option value="">{{ __('Select a Level 3 category') }}</option>
                            <template x-for="category in level3Options" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('crime_category_id')" />
                    </div>
                </div>

                <x-alert tone="info" class="mt-5">
                    {{ __('Categories are provisional, based on publicly available Ethiopian legal and Oromia Police sources. They are not officially approved by Oromia Regional Police.') }}
                </x-alert>
            </x-card>

            <div class="flex flex-wrap items-center gap-4">
                <x-primary-button>{{ __('Create case') }}</x-primary-button>

                <x-link-button :href="route('cases.index')" tone="secondary">
                    {{ __('Cancel') }}
                </x-link-button>
            </div>
        </form>
    </x-page-container>
</x-app-layout>
