<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Case details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'case-assigned')
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Case assigned.') }}
                </div>
            @elseif (session('status') === 'investigation-updated')
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Investigation notes saved.') }}
                </div>
            @elseif (session('status') === 'judgment-recorded')
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Judgment saved.') }}
                </div>
            @elseif (session('status') === 'case-closed')
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Case closed.') }}
                </div>
            @elseif (in_array(session('status'), ['suspect-linked', 'suspect-created', 'suspect-updated', 'victim-linked', 'victim-created', 'victim-updated'], true))
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Case people updated.') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $case->title }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Case record details.') }}</p>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $case->status->value }}</span>
                </div>

                @if ($case->crimeCategory)
                    <div class="mt-6 rounded-lg border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">{{ __('Provisional classification') }}</p>
                        <nav aria-label="{{ __('Crime category classification') }}" class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-900 dark:text-gray-100">
                            @foreach ($case->classification() as $category)
                                @if (! $loop->first)
                                    <span aria-hidden="true" class="text-gray-400 dark:text-gray-500">&rarr;</span>
                                @endif
                                <span>{{ $category['name'] }}</span>
                            @endforeach
                        </nav>
                        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ __('Provisional categories are based on publicly available Ethiopian legal and Oromia Police sources and are not officially approved by Oromia Regional Police.') }}</p>
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Effective case title / type') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->title }} — {{ $case->crime_type }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Crime type') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->crime_type }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Location') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->location }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->status->value }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Incident date') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->incident_date?->format('M d, Y h:i A') ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Creator') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->creator?->name ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Investigator') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->investigator?->name ?? '—' }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Description') }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $case->description }}</p>
                    </div>

                    @if ($case->investigation_notes)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Investigation notes') }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $case->investigation_notes }}</p>
                        </div>
                    @endif

                    @if (filled($case->judgment_notes))
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Judgment notes') }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $case->judgment_notes }}</p>
                        </div>
                    @endif

                    @if ($case->closed_at)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Closure date') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->closed_at->format('M d, Y h:i A') }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Created at') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->created_at?->format('M d, Y h:i A') ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Updated at') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $case->updated_at?->format('M d, Y h:i A') ?? '—' }}</p>
                    </div>
                </div>

                @can('assign', $case)
                    <form method="POST" action="{{ route('cases.assign', $case) }}" class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @csrf
                        @method('PATCH')

                        <div class="max-w-xl">
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Assign investigator') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Only users with the Investigator role can be assigned.') }}</p>

                            <div class="mt-4">
                                <x-input-label for="investigator_id" :value="__('Investigator')" />
                                <select id="investigator_id" name="investigator_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" required>
                                    <option value="" @selected(old('investigator_id', $case->investigator_id) === null)>{{ __('Select an investigator') }}</option>
                                    @foreach ($investigators as $investigator)
                                        <option value="{{ $investigator->id }}" @selected((int) old('investigator_id', $case->investigator_id) === $investigator->id)>{{ $investigator->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('investigator_id')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-primary-button>{{ __('Assign investigator') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                @endcan

                @can('updateInvestigation', $case)
                    <form method="POST" action="{{ route('cases.update_investigation', $case) }}" class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @csrf
                        @method('PATCH')

                        <div class="max-w-xl">
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Update investigation') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Record the latest investigation notes for this case.') }}</p>

                            <div class="mt-4">
                                <x-input-label for="investigation_notes" :value="__('Investigation notes')" />
                                <textarea id="investigation_notes" name="investigation_notes" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" required>{{ old('investigation_notes', $case->investigation_notes) }}</textarea>
                                <x-input-error :messages="$errors->get('investigation_notes')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-primary-button>{{ __('Save investigation notes') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                @endcan

                @can('recordJudgment', $case)
                    <form method="POST" action="{{ route('cases.record_judgment', $case) }}" class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @csrf
                        @method('PATCH')

                        <div class="max-w-xl">
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Judgment') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Record or update the judgment for this case.') }}</p>

                            <div class="mt-4">
                                <x-input-label for="judgment_notes" :value="__('Judgment notes')" />
                                <textarea id="judgment_notes" name="judgment_notes" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" required>{{ old('judgment_notes', $case->judgment_notes) }}</textarea>
                                <x-input-error :messages="$errors->get('judgment_notes')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-primary-button>{{ __('Save judgment') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                @endcan

                @can('close', $case)
                    <form method="POST" action="{{ route('cases.close', $case) }}" class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @csrf
                        @method('PATCH')

                        <div class="max-w-xl">
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Close case') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Close this case after recording a judgment. Closed cases cannot be reopened.') }}</p>

                            <div class="mt-4">
                                <x-primary-button>{{ __('Close case') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                @endcan

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                    <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Suspects') }}</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('People linked to this case and their case-specific status.') }}</p>

                    @if ($case->suspects->isEmpty())
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('No suspects are linked to this case.') }}</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($case->suspects as $suspect)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <a href="{{ route('suspects.show', $suspect) }}" class="font-medium text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ $suspect->full_name }}</a>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($suspect->pivot->status?->value ?? '') }}</p>
                                        </div>

                                        @if (filled($suspect->pivot->notes))
                                            <p class="max-w-xl whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">{{ $suspect->pivot->notes }}</p>
                                        @endif
                                    </div>

                                    @can('managePeople', $case)
                                        <form method="POST" action="{{ route('cases.suspects.update', [$case, $suspect]) }}" class="mt-4 grid gap-4 sm:grid-cols-[1fr_180px_auto] sm:items-end">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label for="suspect_notes_{{ $loop->index }}" :value="__('Notes')" />
                                                <textarea id="suspect_notes_{{ $loop->index }}" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('notes', $suspect->pivot->notes) }}</textarea>
                                            </div>
                                            <div>
                                                <x-input-label for="suspect_status_{{ $loop->index }}" :value="__('Status')" />
                                                <select id="suspect_status_{{ $loop->index }}" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                                    @foreach (\App\Enums\SuspectCaseStatus::cases() as $statusOption)
                                                        <option value="{{ $statusOption->value }}" @selected(old('status', $suspect->pivot->status?->value) === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <x-secondary-button>{{ __('Save') }}</x-secondary-button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @can('managePeople', $case)
                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            <form method="POST" action="{{ route('cases.suspects.attach', $case) }}" class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                @csrf

                                <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Link an existing suspect') }}</h5>

                                <div>
                                    <x-input-label for="suspect_id" :value="__('Suspect')" />
                                    <select id="suspect_id" name="suspect_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        <option value="">{{ __('Select a suspect') }}</option>
                                        @foreach ($availableSuspects as $availableSuspect)
                                            <option value="{{ $availableSuspect->id }}" @selected(old('suspect_id') == $availableSuspect->id)>{{ $availableSuspect->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('suspect_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="attach_suspect_notes" :value="__('Notes')" />
                                    <textarea id="attach_suspect_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('notes') }}</textarea>
                                </div>

                                <div>
                                    <x-input-label for="attach_suspect_status" :value="__('Status')" />
                                    <select id="attach_suspect_status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        @foreach (\App\Enums\SuspectCaseStatus::cases() as $statusOption)
                                            <option value="{{ $statusOption->value }}" @selected(old('status', \App\Enums\SuspectCaseStatus::Identified->value) === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>

                                @if ($availableSuspects->isEmpty())
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No linkable suspects are available.') }}</p>
                                @endif

                                <x-primary-button>{{ __('Link suspect') }}</x-primary-button>
                            </form>

                            <form method="POST" action="{{ route('cases.suspects.store_new', $case) }}" class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                @csrf

                                <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Create and link a suspect') }}</h5>

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
                                    <textarea id="new_suspect_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('notes') }}</textarea>
                                </div>

                                <div>
                                    <x-input-label for="new_suspect_status" :value="__('Status')" />
                                    <select id="new_suspect_status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        @foreach (\App\Enums\SuspectCaseStatus::cases() as $statusOption)
                                            <option value="{{ $statusOption->value }}" @selected(old('status', \App\Enums\SuspectCaseStatus::Identified->value) === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <x-primary-button>{{ __('Create and link suspect') }}</x-primary-button>
                            </form>
                        </div>
                    @endcan
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                    <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Victims') }}</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('People linked to this case as victims.') }}</p>

                    @if ($case->victims->isEmpty())
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('No victims are linked to this case.') }}</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($case->victims as $victim)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <a href="{{ route('victims.show', $victim) }}" class="font-medium text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ $victim->full_name }}</a>

                                        @if (filled($victim->pivot->notes))
                                            <p class="max-w-xl whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">{{ $victim->pivot->notes }}</p>
                                        @endif
                                    </div>

                                    @can('managePeople', $case)
                                        <form method="POST" action="{{ route('cases.victims.update', [$case, $victim]) }}" class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label for="victim_notes_{{ $loop->index }}" :value="__('Notes')" />
                                                <textarea id="victim_notes_{{ $loop->index }}" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('notes', $victim->pivot->notes) }}</textarea>
                                            </div>
                                            <x-secondary-button>{{ __('Save') }}</x-secondary-button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @can('managePeople', $case)
                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            <form method="POST" action="{{ route('cases.victims.attach', $case) }}" class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                @csrf

                                <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Link an existing victim') }}</h5>

                                <div>
                                    <x-input-label for="victim_id" :value="__('Victim')" />
                                    <select id="victim_id" name="victim_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        <option value="">{{ __('Select a victim') }}</option>
                                        @foreach ($availableVictims as $availableVictim)
                                            <option value="{{ $availableVictim->id }}" @selected(old('victim_id') == $availableVictim->id)>{{ $availableVictim->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('victim_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="attach_victim_notes" :value="__('Notes')" />
                                    <textarea id="attach_victim_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('notes') }}</textarea>
                                </div>

                                @if ($availableVictims->isEmpty())
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No linkable victims are available.') }}</p>
                                @endif

                                <x-primary-button>{{ __('Link victim') }}</x-primary-button>
                            </form>

                            <form method="POST" action="{{ route('cases.victims.store_new', $case) }}" class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                @csrf

                                <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Create and link a victim') }}</h5>

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
                                    <textarea id="new_victim_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('notes') }}</textarea>
                                </div>

                                <x-primary-button>{{ __('Create and link victim') }}</x-primary-button>
                            </form>
                        </div>
                    @endcan
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Evidence') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Items registered for this case and their current custody status.') }}</p>
                        </div>

                        @can('create', [\App\Models\Evidence::class, $case])
                            <a href="{{ route('cases.evidence.create', $case) }}" class="text-sm text-indigo-600 underline dark:text-indigo-400">{{ __('Register evidence') }}</a>
                        @endcan
                    </div>

                    @if ($case->evidence->isEmpty())
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('No evidence has been registered for this case.') }}</p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Collected') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Storage location') }}</th>
                                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($case->evidence as $item)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $item->evidence_number }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->title }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($item->evidence_type->value) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-400">{{ $item->collected_at?->format('M d, Y') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($item->status->value) }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $item->storage_location ?: '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <a href="{{ route('evidence.show', $item) }}" class="text-indigo-600 underline dark:text-indigo-400">{{ __('View details') }}</a>
                                                @if ($item->private_file_path)
                                                    @can('download', $item)
                                                        <a href="{{ route('evidence.download', $item) }}" class="ms-3 text-indigo-600 underline dark:text-indigo-400">{{ __('Download') }}</a>
                                                    @endcan
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-8">
                    <a href="{{ route('cases.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        {{ __('Back to case listing') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
