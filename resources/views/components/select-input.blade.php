<select {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-lg border-line bg-surface px-3 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand focus:ring-brand disabled:cursor-not-allowed disabled:bg-canvas disabled:text-muted']) }}>
    {{ $slot }}
</select>
