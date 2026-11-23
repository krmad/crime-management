@props(['label' => '', 'value' => '', 'note' => null, 'tone' => 'brand', 'icon' => 'folder'])

@php
    $tones = [
        'brand' => 'bg-brand-soft text-brand',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'violet' => 'bg-violet-soft text-violet',
        'neutral' => 'bg-canvas text-muted',
    ];
@endphp

<article {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface p-5 shadow-card']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-muted">{{ $label }}</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-ink">{{ $value }}</p>
        </div>

        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>

    @if ($note)
        <p class="mt-3 text-xs text-muted">{{ $note }}</p>
    @endif
</article>
