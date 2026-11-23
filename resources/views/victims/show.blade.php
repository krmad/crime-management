<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Victim details') }}</h2>
    </x-slot>

    <x-page-container>
        <x-card :title="$victim->full_name" :subtitle="__('Profile details shared across linked cases.')">
            <x-slot name="actions">
                @can('update', $victim)
                    <x-link-button :href="route('victims.edit', $victim)" tone="secondary">
                        <x-icon name="file" class="h-4 w-4" />
                        {{ __('Edit victim') }}
                    </x-link-button>
                @endcan
            </x-slot>

            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div class="rounded-lg bg-canvas p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Date of birth') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $victim->date_of_birth?->format('M d, Y') ?? '—' }}</dd>
                </div>

                <div class="rounded-lg bg-canvas p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Gender') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $victim->gender ?? '—' }}</dd>
                </div>

                <div class="rounded-lg bg-canvas p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $victim->phone ?? '—' }}</dd>
                </div>

                <div class="rounded-lg bg-canvas p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Address') }}</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm font-medium text-ink">{{ $victim->address ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card :title="__('Linked cases')" :subtitle="__('Only cases available to your role are shown.')" :padded="false">
            @if ($cases->isEmpty())
                <div class="p-5">
                    <x-empty-state :title="__('No visible cases are linked to this victim.')" icon="cases" />
                </div>
            @else
                <x-table min-width="wide">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Case') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Notes') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($cases as $case)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="px-5 py-4 font-semibold text-brand">
                                <a href="{{ route('cases.show', $case) }}" class="hover:underline">{{ $case->title }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <x-status-badge :status="$case->status->value" />
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $case->pivot->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="border-t border-line bg-canvas px-5 py-3">
                    {{ $cases->links() }}
                </div>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
