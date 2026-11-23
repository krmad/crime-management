@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-success/25 bg-success-soft px-3 py-2 text-sm font-medium text-success-ink dark:text-success']) }}>
        {{ $status }}
    </div>
@endif
