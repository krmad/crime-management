@props([
    'latitude' => null,
    'longitude' => null,
    'id' => 'incident-location-search',
])

@php
    $config = [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'tileUrl' => config('services.map.tile_url'),
        'attribution' => config('services.map.attribution'),
        'center' => [
            (float) config('services.map.default_latitude'),
            (float) config('services.map.default_longitude'),
        ],
        'zoom' => (int) config('services.map.default_zoom'),
        'detailZoom' => 13,
        'searchUrl' => route('case-locations.search'),
        'strings' => [
            'noCoordinates' => __('No coordinates selected'),
            'enterMoreCharacters' => __('Enter at least 3 characters'),
            'searchUnavailable' => __('Location search unavailable'),
            'noResults' => __('No location results found'),
        ],
    ];
@endphp

<x-card :title="__('Incident location map')" :subtitle="__('Add exact coordinates using the map')">
    <div x-data="incidentLocationPicker({{ \Illuminate\Support\Js::from($config) }})" class="space-y-4">
        <div>
            <x-input-label :for="$id" :value="__('Address or place search')" />
            <div class="mt-1 flex gap-2">
                <x-text-input
                    :id="$id"
                    type="search"
                    x-model="query"
                    x-on:keydown.enter.prevent="search()"
                    x-on:click.outside="results = []"
                    maxlength="200"
                    autocomplete="off"
                    placeholder="{{ __('Address or place search') }}"
                />
                <x-primary-button type="button" x-on:click="search()" x-bind:disabled="searching">
                    <span x-text="searching ? '{{ __('Searching...') }}' : '{{ __('Search') }}'">{{ __('Search') }}</span>
                </x-primary-button>
            </div>
            <p class="mt-1.5 text-xs text-muted">{{ __('Select a result to place the marker') }}</p>
        </div>

        <div x-show="results.length > 0" x-cloak class="rounded-lg border border-line bg-canvas p-2">
            <p class="px-2 pb-1.5 text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Search results') }}</p>
            <ul class="max-h-44 space-y-1 overflow-y-auto">
                <template x-for="(result, index) in results" :key="index">
                    <li>
                        <button
                            type="button"
                            class="w-full rounded-md px-2 py-2 text-start text-sm text-ink transition hover:bg-brand-soft hover:text-brand-ink focus:outline-none focus:ring-2 focus:ring-brand"
                            x-text="result.label"
                            x-on:click="chooseResult(result)"
                        ></button>
                    </li>
                </template>
            </ul>
        </div>

        <p x-show="searchMessage !== ''" x-cloak class="text-xs text-danger" x-text="searchMessage"></p>

        <div x-ref="map" class="h-72 w-full overflow-hidden rounded-lg border border-line bg-canvas dark:bg-surface" role="application" aria-label="{{ __('Incident location map') }}"></div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Coordinates') }}</p>
                <p class="mt-1 text-sm font-medium text-ink" x-text="coordinatesLabel"></p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    x-show="hasCoordinates()"
                    x-cloak
                    x-bind:href="osmUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-line bg-surface px-4 py-2.5 text-sm font-semibold text-brand shadow-sm transition hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
                >
                    {{ __('Open in OpenStreetMap') }}
                </a>
                <x-secondary-button type="button" x-on:click="clearCoordinates()">
                    {{ __('Clear coordinates') }}
                </x-secondary-button>
            </div>
        </div>

        <input type="hidden" name="latitude" :value="latitude" />
        <input type="hidden" name="longitude" :value="longitude" />

        <div class="grid gap-1">
            <x-input-error :messages="$errors->get('latitude')" />
            <x-input-error :messages="$errors->get('longitude')" />
        </div>
    </div>
</x-card>
