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

                <div class="mt-8">
                    <a href="{{ route('cases.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        {{ __('Back to case listing') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
