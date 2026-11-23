@props(['minWidth' => 'normal'])

@php
    $minWidths = [
        'none' => 'min-w-0',
        'normal' => 'min-w-[720px]',
        'wide' => 'min-w-[1040px]',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-line bg-surface shadow-card']) }}>
    <table class="w-full {{ $minWidths[$minWidth] ?? $minWidths['normal'] }} text-left text-sm">
        @isset($head)
            <thead class="bg-canvas text-xs uppercase tracking-wide text-muted">
                {{ $head }}
            </thead>
        @endisset

        <tbody class="divide-y divide-line">
            {{ $slot }}
        </tbody>
    </table>
</div>
