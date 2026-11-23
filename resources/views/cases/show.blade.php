<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Case details') }}</h2>
    </x-slot>

    <x-page-container>
        @if (session('status') === 'case-assigned')
            <x-alert tone="success">{{ __('Case assigned.') }}</x-alert>
        @elseif (session('status') === 'investigation-updated')
            <x-alert tone="success">{{ __('Investigation notes saved.') }}</x-alert>
        @elseif (session('status') === 'judgment-recorded')
            <x-alert tone="success">{{ __('Judgment saved.') }}</x-alert>
        @elseif (session('status') === 'case-closed')
            <x-alert tone="success">{{ __('Case closed.') }}</x-alert>
        @elseif (in_array(session('status'), ['suspect-linked', 'suspect-created', 'suspect-updated', 'victim-linked', 'victim-created', 'victim-updated'], true))
            <x-alert tone="success">{{ __('Case people updated.') }}</x-alert>
        @endif

        <x-card :title="__('Case overview')" :subtitle="__('Case record details.')">
            <x-slot name="actions">
                <x-status-badge :status="$case->status->value" />
            </x-slot>

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $case->title }}</h1>

            <div class="mt-6 grid gap-x-6 gap-y-5 sm:grid-cols-2 xl:grid-cols-3">
                <div class="sm:col-span-2 xl:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Effective case title / type') }}</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $case->title }} — {{ $case->crime_type }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Crime type') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->crime_type }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Location') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->location }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Status') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ \Illuminate\Support\Str::headline($case->status->value) }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Incident date') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->incident_date?->format('M d, Y h:i A') ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Creator') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->creator?->name ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Investigator') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->investigator?->name ?? '—' }}</p>
                </div>

                <div class="sm:col-span-2 xl:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Description') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $case->description }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Created at') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->created_at?->format('M d, Y h:i A') ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Updated at') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->updated_at?->format('M d, Y h:i A') ?? '—' }}</p>
                </div>
            </div>
        </x-card>

        @if ($case->crimeCategory)
            <x-card :title="__('Crime classification')">
                <div class="rounded-lg border border-brand/20 bg-brand-soft/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand">{{ __('Provisional classification') }}</p>
                    <nav aria-label="{{ __('Crime category classification') }}" class="mt-3 flex flex-wrap items-center gap-2 text-sm font-medium text-ink">
                        @foreach ($case->classification() as $category)
                            @if (! $loop->first)
                                <span aria-hidden="true" class="text-muted">&rarr;</span>
                            @endif
                            <span>{{ $category['name'] }}</span>
                        @endforeach
                    </nav>
                    <p class="mt-3 text-xs text-muted">{{ __('Provisional categories are based on publicly available Ethiopian legal and Oromia Police sources and are not officially approved by Oromia Regional Police.') }}</p>
                </div>
            </x-card>
        @endif

        @can('assign', $case)
            <x-card :title="__('Assign investigator')" :subtitle="__('Only users with the Investigator role can be assigned.')">
                <form method="POST" action="{{ route('cases.assign', $case) }}" class="max-w-xl space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="investigator_id" :value="__('Investigator')" />
                        <x-select-input id="investigator_id" name="investigator_id" required>
                            <option value="" @selected(old('investigator_id', $case->investigator_id) === null)>{{ __('Select an investigator') }}</option>
                            @foreach ($investigators as $investigator)
                                <option value="{{ $investigator->id }}" @selected((int) old('investigator_id', $case->investigator_id) === $investigator->id)>{{ $investigator->name }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('investigator_id')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Assign investigator') }}</x-primary-button>
                </form>
            </x-card>
        @endcan

        <x-card :title="__('Investigation')" :subtitle="__('Record the latest investigation notes for this case.')">
            @if ($case->investigation_notes)
                <div class="rounded-lg border border-line bg-canvas p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Investigation notes') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $case->investigation_notes }}</p>
                </div>
            @endif

            @can('updateInvestigation', $case)
                <form method="POST" action="{{ route('cases.update_investigation', $case) }}" class="mt-5 max-w-xl space-y-4 border-t border-line pt-5">
                    @csrf
                    @method('PATCH')

                    <h4 class="text-base font-semibold text-ink">{{ __('Update investigation') }}</h4>

                    <div>
                        <x-input-label for="investigation_notes" :value="__('Investigation notes')" />
                        <x-textarea-input id="investigation_notes" name="investigation_notes" rows="5" required>{{ old('investigation_notes', $case->investigation_notes) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('investigation_notes')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Save investigation notes') }}</x-primary-button>
                </form>
            @endcan
        </x-card>

        <x-card :title="__('Judgment')" :subtitle="__('Record or update the judgment for this case.')">
            @if (filled($case->judgment_notes))
                <div class="rounded-lg border border-violet/20 bg-violet-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet">{{ __('Judgment notes') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $case->judgment_notes }}</p>
                </div>
            @endif

            @can('recordJudgment', $case)
                <form method="POST" action="{{ route('cases.record_judgment', $case) }}" class="mt-5 max-w-xl space-y-4 border-t border-line pt-5">
                    @csrf
                    @method('PATCH')

                    <h4 class="text-base font-semibold text-ink">{{ __('Judgment') }}</h4>

                    <div>
                        <x-input-label for="judgment_notes" :value="__('Judgment notes')" />
                        <x-textarea-input id="judgment_notes" name="judgment_notes" rows="5" required>{{ old('judgment_notes', $case->judgment_notes) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('judgment_notes')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Save judgment') }}</x-primary-button>
                </form>
            @endcan
        </x-card>

        <x-card :title="__('Closure')" :subtitle="__('Close this case after recording a judgment. Closed cases cannot be reopened.')">
            @if ($case->closed_at)
                <div class="rounded-lg border border-success/20 bg-success-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-success">{{ __('Closure date') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $case->closed_at->format('M d, Y h:i A') }}</p>
                </div>
            @endif

            @can('close', $case)
                <form method="POST" action="{{ route('cases.close', $case) }}" class="mt-5 max-w-xl space-y-4 border-t border-line pt-5">
                    @csrf
                    @method('PATCH')

                    <h4 class="text-base font-semibold text-ink">{{ __('Close case') }}</h4>
                    <x-primary-button>{{ __('Close case') }}</x-primary-button>
                </form>
            @endcan
        </x-card>

        <x-card :title="__('Suspects')" :subtitle="__('People linked to this case and their case-specific status.')">
            @if ($case->suspects->isEmpty())
                <x-empty-state :title="__('No known suspect at time of report')" icon="suspect" />
            @else
                <div class="space-y-3">
                    @foreach ($case->suspects as $suspect)
                        <div class="rounded-lg border border-line bg-canvas p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('suspects.show', $suspect) }}" class="font-semibold text-brand transition hover:text-brand-dark">{{ $suspect->full_name }}</a>
                                    @if (filled($suspect->alias))
                                        <p class="mt-1 text-xs text-muted">{{ __('Alias') }}: {{ $suspect->alias }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-muted">{{ \Illuminate\Support\Str::headline($suspect->pivot->status?->value ?? '') }}</p>
                                </div>

                                @if (filled($suspect->pivot->notes))
                                    <p class="max-w-xl whitespace-pre-line text-sm text-muted">{{ $suspect->pivot->notes }}</p>
                                @endif
                            </div>

                            @can('managePeople', $case)
                                <form method="POST" action="{{ route('cases.suspects.update', [$case, $suspect]) }}" class="mt-4 grid gap-4 border-t border-line pt-4 sm:grid-cols-[1fr_180px_auto] sm:items-end">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <x-input-label for="suspect_notes_{{ $loop->index }}" :value="__('Notes')" />
                                        <x-textarea-input id="suspect_notes_{{ $loop->index }}" name="notes" rows="2">{{ old('notes', $suspect->pivot->notes) }}</x-textarea-input>
                                    </div>
                                    <div>
                                        <x-input-label for="suspect_status_{{ $loop->index }}" :value="__('Status')" />
                                        <x-select-input id="suspect_status_{{ $loop->index }}" name="status" required>
                                            @foreach (\App\Enums\SuspectCaseStatus::cases() as $statusOption)
                                                <option value="{{ $statusOption->value }}" @selected(old('status', $suspect->pivot->status?->value) === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                                            @endforeach
                                        </x-select-input>
                                    </div>
                                    <x-secondary-button>{{ __('Save') }}</x-secondary-button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif

            @can('managePeople', $case)
                <div class="mt-6 grid gap-5 border-t border-line pt-6 xl:grid-cols-2">
                    <form method="POST" action="{{ route('cases.suspects.attach', $case) }}" class="space-y-4 rounded-lg border border-line bg-canvas p-4">
                        @csrf

                        <h5 class="font-semibold text-ink">{{ __('Link an existing suspect') }}</h5>

                        <div>
                            <x-input-label for="suspect_id" :value="__('Suspect')" />
                            <x-select-input id="suspect_id" name="suspect_id" required>
                                <option value="">{{ __('Select a suspect') }}</option>
                                @foreach ($availableSuspects as $availableSuspect)
                                    <option value="{{ $availableSuspect->id }}" @selected(old('suspect_id') == $availableSuspect->id)>{{ $availableSuspect->full_name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('suspect_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="attach_suspect_notes" :value="__('Notes')" />
                            <x-textarea-input id="attach_suspect_notes" name="notes" rows="2">{{ old('notes') }}</x-textarea-input>
                        </div>

                        <div>
                            <x-input-label for="attach_suspect_status" :value="__('Status')" />
                            <x-select-input id="attach_suspect_status" name="status" required>
                                @foreach (\App\Enums\SuspectCaseStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected(old('status', \App\Enums\SuspectCaseStatus::Identified->value) === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        @if ($availableSuspects->isEmpty())
                            <p class="text-sm text-muted">{{ __('No linkable suspects are available.') }}</p>
                        @endif

                        <x-primary-button>{{ __('Link suspect') }}</x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('cases.suspects.store_new', $case) }}" class="space-y-4 rounded-lg border border-line bg-canvas p-4">
                        @csrf

                        <h5 class="font-semibold text-ink">{{ __('Create and link a suspect') }}</h5>

                        <div>
                            <x-input-label for="new_suspect_full_name" :value="__('Full name')" />
                            <x-text-input id="new_suspect_full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name')" required />
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="new_suspect_alias" :value="__('Alias')" />
                                <x-text-input id="new_suspect_alias" name="alias" type="text" class="mt-1 block w-full" :value="old('alias')" />
                            </div>
                            <div>
                                <x-input-label for="new_suspect_date_of_birth" :value="__('Date of birth')" />
                                <x-text-input id="new_suspect_date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="new_suspect_notes" :value="__('Notes')" />
                            <x-textarea-input id="new_suspect_notes" name="notes" rows="2">{{ old('notes') }}</x-textarea-input>
                        </div>

                        <div>
                            <x-input-label for="new_suspect_status" :value="__('Status')" />
                            <x-select-input id="new_suspect_status" name="status" required>
                                @foreach (\App\Enums\SuspectCaseStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected(old('status', \App\Enums\SuspectCaseStatus::Identified->value) === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                                @endforeach
                            </x-select-input>
                        </div>

                        <x-primary-button>{{ __('Create and link suspect') }}</x-primary-button>
                    </form>
                </div>
            @endcan
        </x-card>

        <x-card :title="__('Victims')" :subtitle="__('People linked to this case as victims.')">
            @if ($case->victims->isEmpty())
                <x-empty-state :title="__('No victims are linked to this case.')" icon="victim" />
            @else
                <div class="space-y-3">
                    @foreach ($case->victims as $victim)
                        <div class="rounded-lg border border-line bg-canvas p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <a href="{{ route('victims.show', $victim) }}" class="font-semibold text-brand transition hover:text-brand-dark">{{ $victim->full_name }}</a>

                                @if (filled($victim->pivot->notes))
                                    <p class="max-w-xl whitespace-pre-line text-sm text-muted">{{ $victim->pivot->notes }}</p>
                                @endif
                            </div>

                            @can('managePeople', $case)
                                <form method="POST" action="{{ route('cases.victims.update', [$case, $victim]) }}" class="mt-4 grid gap-4 border-t border-line pt-4 sm:grid-cols-[1fr_auto] sm:items-end">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <x-input-label for="victim_notes_{{ $loop->index }}" :value="__('Notes')" />
                                        <x-textarea-input id="victim_notes_{{ $loop->index }}" name="notes" rows="2">{{ old('notes', $victim->pivot->notes) }}</x-textarea-input>
                                    </div>
                                    <x-secondary-button>{{ __('Save') }}</x-secondary-button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif

            @can('managePeople', $case)
                <div class="mt-6 grid gap-5 border-t border-line pt-6 xl:grid-cols-2">
                    <form method="POST" action="{{ route('cases.victims.attach', $case) }}" class="space-y-4 rounded-lg border border-line bg-canvas p-4">
                        @csrf

                        <h5 class="font-semibold text-ink">{{ __('Link an existing victim') }}</h5>

                        <div>
                            <x-input-label for="victim_id" :value="__('Victim')" />
                            <x-select-input id="victim_id" name="victim_id" required>
                                <option value="">{{ __('Select a victim') }}</option>
                                @foreach ($availableVictims as $availableVictim)
                                    <option value="{{ $availableVictim->id }}" @selected(old('victim_id') == $availableVictim->id)>{{ $availableVictim->full_name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('victim_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="attach_victim_notes" :value="__('Notes')" />
                            <x-textarea-input id="attach_victim_notes" name="notes" rows="2">{{ old('notes') }}</x-textarea-input>
                        </div>

                        @if ($availableVictims->isEmpty())
                            <p class="text-sm text-muted">{{ __('No linkable victims are available.') }}</p>
                        @endif

                        <x-primary-button>{{ __('Link victim') }}</x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('cases.victims.store_new', $case) }}" class="space-y-4 rounded-lg border border-line bg-canvas p-4">
                        @csrf

                        <h5 class="font-semibold text-ink">{{ __('Create and link a victim') }}</h5>

                        <div>
                            <x-input-label for="new_victim_full_name" :value="__('Full name')" />
                            <x-text-input id="new_victim_full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name')" required />
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="new_victim_date_of_birth" :value="__('Date of birth')" />
                                <x-text-input id="new_victim_date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" />
                            </div>
                            <div>
                                <x-input-label for="new_victim_phone" :value="__('Phone')" />
                                <x-text-input id="new_victim_phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="new_victim_notes" :value="__('Notes')" />
                            <x-textarea-input id="new_victim_notes" name="notes" rows="2">{{ old('notes') }}</x-textarea-input>
                        </div>

                        <x-primary-button>{{ __('Create and link victim') }}</x-primary-button>
                    </form>
                </div>
            @endcan
        </x-card>

        <x-card :title="__('Evidence')" :subtitle="__('Items registered for this case and their current custody status.')" :padded="false">
            <x-slot name="actions">
                @can('create', [\App\Models\Evidence::class, $case])
                    <x-link-button :href="route('cases.evidence.create', $case)" tone="secondary">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Register evidence') }}
                    </x-link-button>
                @endcan
            </x-slot>

            @if ($case->evidence->isEmpty())
                <div class="p-5">
                    <x-empty-state :title="__('No evidence has been registered for this case.')" icon="evidence" />
                </div>
            @else
                <x-table min-width="normal">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Reference') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Title') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Type') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Collected') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Storage location') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($case->evidence as $item)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-brand">{{ $item->evidence_number }}</td>
                            <td class="px-5 py-4 text-ink">{{ $item->title }}</td>
                            <td class="px-5 py-4 text-muted">{{ \Illuminate\Support\Str::headline($item->evidence_type->value) }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $item->collected_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <x-status-badge :status="$item->status->value" />
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $item->storage_location ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <a href="{{ route('evidence.show', $item) }}" class="font-semibold text-brand transition hover:text-brand-dark">{{ __('View details') }}</a>
                                @if ($item->private_file_path)
                                    @can('download', $item)
                                        <a href="{{ route('evidence.download', $item) }}" class="ms-3 font-semibold text-brand transition hover:text-brand-dark">{{ __('Download') }}</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </x-card>

        <div class="flex justify-end">
            <x-link-button :href="route('cases.index')" tone="secondary">
                <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
                {{ __('Back to case listing') }}
            </x-link-button>
        </div>
    </x-page-container>
</x-app-layout>
