@props(['class' => ''])

<button type="button"
    data-theme-toggle
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            document.documentElement.style.colorScheme = this.dark ? 'dark' : 'light';
            try {
                localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            } catch (error) {}
        }
    }"
    @click="toggle()"
    :aria-label="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
    :title="dark ? '{{ __('Light mode') }}' : '{{ __('Dark mode') }}'"
    {{ $attributes->merge(['class' => 'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-surface text-muted shadow-sm transition hover:bg-canvas hover:text-ink focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 '.$class]) }}>
    <x-icon name="moon" class="h-4 w-4" x-show="!dark" x-cloak />
    <x-icon name="sun" class="h-4 w-4" x-show="dark" x-cloak />
</button>
