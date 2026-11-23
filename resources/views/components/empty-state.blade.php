@props(['title' => 'Nothing to show', 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-line bg-surface px-5 py-10 text-center']) }}>
    <x-icon :name="$icon" class="mx-auto h-8 w-8 text-muted/70" />
    <p class="mt-3 text-sm font-semibold text-ink">{{ $title }}</p>

    @isset($description)
        <p class="mx-auto mt-1 max-w-md text-sm text-muted">{{ $description }}</p>
    @endisset

    @isset($actions)
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
