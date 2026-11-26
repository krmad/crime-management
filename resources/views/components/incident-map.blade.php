@props([
    'cases' => [],
    'mappedCasesCount' => null,
])

@php
    $points = collect($cases)
        ->map(fn (\App\Models\CrimeCase $case): array => [
            'title' => $case->title,
            'location' => $case->location,
            'latitude' => (string) $case->latitude,
            'longitude' => (string) $case->longitude,
            'url' => route('cases.show', $case),
        ])
        ->values()
        ->all();

    $config = [
        'cases' => $points,
        'tileUrl' => config('services.map.tile_url'),
        'attribution' => config('services.map.attribution'),
        'center' => [
            (float) config('services.map.default_latitude'),
            (float) config('services.map.default_longitude'),
        ],
        'zoom' => (int) config('services.map.default_zoom'),
        'detailZoom' => 13,
    ];
@endphp

<x-card :title="__('Incident location map')" :subtitle="__('Visible mapped cases')" :padded="false" {{ $attributes->merge() }}>
    @if ($points === [])
        <div class="p-5">
            <x-empty-state :title="__('No coordinates selected')" icon="location">
                <x-slot name="description">{{ __('Add exact coordinates using the map') }}</x-slot>
            </x-empty-state>
        </div>
    @else
        <div class="space-y-3 p-5">
            @if ($mappedCasesCount !== null && $mappedCasesCount > count($points))
                <p class="text-xs text-muted">
                    {{ __('Showing :shown of :total mapped cases', ['shown' => count($points), 'total' => $mappedCasesCount]) }}
                </p>
            @endif

            <div
                x-data="incidentLocationMap({{ \Illuminate\Support\Js::from($config) }})"
                x-ref="map"
                class="h-72 w-full overflow-hidden rounded-lg border border-line bg-canvas dark:bg-surface"
                role="application"
                aria-label="{{ __('Incident location map') }}"
            ></div>
        </div>
    @endif
</x-card>
