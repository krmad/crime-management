@props(['title' => null, 'subtitle' => null, 'padded' => true])

@php
    $hasHeader = $title || $subtitle || isset($actions);
@endphp

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-line bg-surface shadow-card']) }}>
    @if ($hasHeader)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
                @endif

                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padded ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</section>
