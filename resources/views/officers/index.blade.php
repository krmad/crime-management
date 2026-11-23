<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Officers Management') }}</h2>
    </x-slot>

    @php
        $statusMessages = [
            'officer-created' => 'Officer profile created.',
            'officer-updated' => 'Officer profile updated.',
        ];
    @endphp

    <x-page-container>
        @if (isset($statusMessages[session('status')]))
            <x-alert tone="success">{{ __($statusMessages[session('status')]) }}</x-alert>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Officer profiles') }}</h1>
                <p class="mt-1 text-sm text-muted">{{ __('Employment status is separate from login account status.') }}</p>
            </div>

            <x-link-button :href="route('officers.create')">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Add officer profile') }}
            </x-link-button>
        </div>

        <x-card :padded="false">
            <div class="p-5">
                <form method="GET" action="{{ route('officers.index') }}" class="grid gap-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Name or badge number')" />
                        <x-text-input id="search" name="search" type="search" :value="$search" placeholder="Search officers" />
                    </div>
                    <div>
                        <x-input-label for="rank" :value="__('Rank')" />
                        <x-select-input id="rank" name="rank">
                            <option value="">{{ __('All ranks') }}</option>
                            @foreach ($ranks as $rankOption)
                                <option value="{{ $rankOption }}" @selected($rank === $rankOption)>{{ $rankOption }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="employment_status" :value="__('Employment status')" />
                        <x-select-input id="employment_status" name="employment_status">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Enums\EmploymentStatus::cases() as $employmentStatusOption)
                                <option value="{{ $employmentStatusOption->value }}" @selected($employmentStatus === $employmentStatusOption->value)>{{ \Illuminate\Support\Str::headline($employmentStatusOption->value) }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                </form>
            </div>

            @if ($officers->isEmpty())
                <div class="p-5">
                    <x-empty-state title="{{ __('No officer profiles match the selected filters.') }}" icon="officer" />
                </div>
            @else
                <x-table min-width="wide">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Officer') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Badge number') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Rank') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Station') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Employment status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Account status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($officers as $officerProfile)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="px-5 py-4 font-semibold text-ink">{{ $officerProfile->user?->name }}</td>
                            <td class="px-5 py-4 font-medium text-brand">{{ $officerProfile->badge_number ?? '—' }}</td>
                            <td class="px-5 py-4 text-muted">{{ $officerProfile->rank ?? '—' }}</td>
                            <td class="px-5 py-4 text-muted">{{ $officerProfile->police_station ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <x-status-badge :status="$officerProfile->employment_status->value" />
                            </td>
                            <td class="px-5 py-4">
                                @if ($officerProfile->user?->is_active)
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success-ink dark:text-success">{{ __('Active') }}</span>
                                @else
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-danger-soft px-2.5 py-1 text-xs font-semibold text-danger-ink dark:text-danger">{{ __('Deactivated') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-4">
                                    <a href="{{ route('officers.show', $officerProfile) }}" class="text-sm font-semibold text-brand hover:underline">{{ __('View') }}</a>
                                    <a href="{{ route('officers.edit', $officerProfile) }}" class="text-sm font-semibold text-brand hover:underline">{{ __('Edit') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="p-5">
                    {{ $officers->links() }}
                </div>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
