@props(['width' => '7xl'])

@php
    $widths = [
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        'full' => 'max-w-none',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto w-full '.($widths[$width] ?? $widths['7xl']).' space-y-6 px-4 py-6 sm:px-6 lg:px-8']) }}>
    {{ $slot }}
</div>
