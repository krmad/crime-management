<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Officer profile') }}</h2>
    </x-slot>

    <x-page-container width="6xl">
        <x-card :padded="false">
            <x-slot name="actions">
                <x-link-button :href="route('officers.edit', $officerProfile)">
                    <x-icon name="profile" class="h-4 w-4" />
                    {{ __('Edit profile') }}
                </x-link-button>
            </x-slot>

            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-soft text-sm font-semibold text-brand-ink dark:text-brand">
                        {{ strtoupper(substr($officerProfile->user?->name ?? '?', 0, 1)) }}
                    </span>
                    <div>
                        <h1 class="text-lg font-semibold text-ink">{{ $officerProfile->user?->name }}</h1>
                        <p class="mt-0.5 text-sm text-muted">{{ $officerProfile->user?->email }}</p>
                    </div>
                </div>

                <x-status-badge :status="$officerProfile->employment_status->value" />
            </div>

            <dl class="grid gap-6 p-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Badge number') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->badge_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Rank') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->rank ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Department') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->department ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Police station') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->police_station ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Employment status') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ \Illuminate\Support\Str::headline($officerProfile->employment_status->value) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Account role') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->user ? \Illuminate\Support\Str::headline($officerProfile->user->role->value) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Account status') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $officerProfile->user?->is_active ? __('Active') : __('Deactivated') }}</dd>
                </div>
            </dl>

            <div class="border-t border-line p-5">
                <x-link-button :href="route('officers.index')" tone="secondary">
                    <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
                    {{ __('Back to officers') }}
                </x-link-button>
            </div>
        </x-card>
    </x-page-container>
</x-app-layout>
