@props(['active' => false, 'icon' => null])

@php
    $classes = $active
        ? 'bg-brand-soft font-semibold text-brand-ink dark:text-brand'
        : 'text-muted hover:bg-canvas hover:text-ink';
@endphp

<a {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors '.$classes]) }} @if ($active) aria-current="page" @endif>
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4 shrink-0" />
    @endif

    <span>{{ $slot }}</span>
</a>
