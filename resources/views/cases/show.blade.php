<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Case details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

                <div class="mt-8">
                    <a href="{{ route('cases.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        {{ __('Back to case listing') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
