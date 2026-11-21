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
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $case->title }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Case record details.') }}</p>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $case->status->value }}</span>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
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

                    @if ($case->judgment_notes)
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

                <div class="mt-8">
                    <a href="{{ route('cases.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        {{ __('Back to case listing') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
