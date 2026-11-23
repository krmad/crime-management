@props(['tone' => 'success'])

@php
    $tones = [
        'success' => ['border-success/25 bg-success-soft text-success-ink dark:text-success', 'check'],
        'warning' => ['border-warning/25 bg-warning-soft text-warning-ink dark:text-warning', 'alert'],
        'danger' => ['border-danger/25 bg-danger-soft text-danger-ink dark:text-danger', 'alert'],
        'info' => ['border-brand/25 bg-brand-soft text-brand-ink dark:text-brand', 'info'],
    ];

    [$classes, $icon] = $tones[$tone] ?? $tones['success'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$classes]) }} role="status">
    <x-icon :name="$icon" class="mt-0.5 h-4 w-4 shrink-0" />
    <div>{{ $slot }}</div>
</div>
