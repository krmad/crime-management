@props(['href' => '#', 'tone' => 'primary'])

@php
    $tones = [
        'primary' => 'bg-brand text-slate-950 hover:bg-brand/90 focus:ring-brand',
        'secondary' => 'border border-line bg-surface text-ink hover:bg-canvas focus:ring-brand',
        'danger' => 'bg-danger text-white hover:bg-danger/90 focus:ring-danger dark:text-slate-950',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 '.($tones[$tone] ?? $tones['primary'])]) }}>
    {{ $slot }}
</a>
