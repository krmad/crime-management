@props(['status' => ''])

@php
    $normalized = strtolower(str_replace([' ', '_'], '-', (string) $status));

    $tones = [
        'open' => 'bg-warning-soft text-warning-ink dark:text-warning',
        'under-investigation' => 'bg-brand-soft text-brand-ink dark:text-brand',
        'judgment-recorded' => 'bg-violet-soft text-violet-ink dark:text-violet',
        'closed' => 'bg-success-soft text-success-ink dark:text-success',
        'logged' => 'bg-brand-soft text-brand-ink dark:text-brand',
        'in-custody' => 'bg-brand-soft text-brand-ink dark:text-brand',
        'collected' => 'bg-brand-soft text-brand-ink dark:text-brand',
        'stored' => 'bg-brand-soft text-brand-ink dark:text-brand',
        'transferred' => 'bg-warning-soft text-warning-ink dark:text-warning',
        'released' => 'bg-success-soft text-success-ink dark:text-success',
        'disposed' => 'bg-canvas text-muted',
        'identified' => 'bg-brand-soft text-brand-ink dark:text-brand',
        'cleared' => 'bg-success-soft text-success-ink dark:text-success',
        'charged' => 'bg-warning-soft text-warning-ink dark:text-warning',
        'suspended' => 'bg-warning-soft text-warning-ink dark:text-warning',
        'retired' => 'bg-canvas text-muted',
        'active' => 'bg-success-soft text-success-ink dark:text-success',
        'inactive' => 'bg-canvas text-muted',
        'yes' => 'bg-success-soft text-success-ink dark:text-success',
        'no' => 'bg-canvas text-muted',
    ];

    $tone = $tones[$normalized] ?? 'bg-canvas text-muted';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold '.$tone]) }}>
    {{ \Illuminate\Support\Str::headline((string) $status) }}
</span>
