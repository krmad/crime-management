<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Evidence details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (in_array(session('status'), ['evidence-updated', 'evidence-file-uploaded', 'custody-recorded'], true))
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Evidence record updated.') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $evidence->evidence_number }}</p>
                        <h3 class="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">{{ $evidence->title }}</h3>
                        <a href="{{ route('cases.show', $evidence->case) }}" class="mt-1 block text-sm text-indigo-600 underline dark:text-indigo-400">{{ $evidence->case->title }}</a>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ \Illuminate\Support\Str::headline($evidence->status->value) }}</span>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Type') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ \Illuminate\Support\Str::headline($evidence->evidence_type->value) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Collected at') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $evidence->collected_at?->format('M d, Y h:i A') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Collected by') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $evidence->collector?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Storage location') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $evidence->storage_location ?: '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Description') }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $evidence->description ?: '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Supporting file') }}</p>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $evidence->original_filename ?: __('No file attached.') }}</p>
                        @if ($evidence->mime_type)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $evidence->mime_type }} @if ($evidence->file_size) &middot; {{ $evidence->formattedFileSize() }} @endif</p>
                        @endif
                        @if ($evidence->private_file_path)
                            @can('download', $evidence)
                                <a href="{{ route('evidence.download', $evidence) }}" class="mt-2 inline-block text-sm text-indigo-600 underline dark:text-indigo-400">{{ __('Download file') }}</a>
                            @endcan
                        @endif
                    </div>
                </div>

                @can('update', $evidence)
                    <div class="mt-6 flex flex-wrap gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <a href="{{ route('evidence.edit', $evidence) }}" class="text-sm text-indigo-600 underline dark:text-indigo-400">{{ __('Edit metadata') }}</a>
                    </div>
                @endcan
            </div>

            @can('update', $evidence)
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Attach supporting file') }}</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Files are stored in private storage and require authorization to download.') }}</p>

                    <form method="POST" action="{{ route('evidence.file.store', $evidence) }}" enctype="multipart/form-data" class="mt-4 max-w-xl space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="attachment" :value="__('File')" />
                            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm text-gray-700 file:me-2 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-white dark:text-gray-300" required />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Allowed: PDF, Word, text, CSV, JPG, PNG, WebP. Maximum 20 MB.') }}</p>
                            <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Upload file') }}</x-primary-button>
                    </form>
                </div>
            @endcan

            @can('update', $evidence)
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Record custody event') }}</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Custody events are permanent and cannot be edited or deleted.') }}</p>

                    <form method="POST" action="{{ route('evidence.custody.store', $evidence) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf

                        <div>
                            <x-input-label for="event_type" :value="__('Event')" />
                            <select id="event_type" name="event_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                <option value="transfer">{{ __('Transfer') }}</option>
                                <option value="location_change">{{ __('Storage location change') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="received_by" :value="__('Received by')" />
                            <select id="received_by" name="received_by" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">{{ __('Select a user') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('received_by') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('received_by')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="to_location" :value="__('To location')" />
                            <x-text-input id="to_location" name="to_location" type="text" class="mt-1 block w-full" :value="old('to_location')" required />
                            <x-input-error :messages="$errors->get('to_location')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="occurred_at" :value="__('Occurred at')" />
                            <x-text-input id="occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full" :value="old('occurred_at', now()->format('Y-m-d\TH:i'))" required />
                            <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-primary-button>{{ __('Record event') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            @can('dispose', $evidence)
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Release or disposal') }}</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Only administrators may record release or disposal events.') }}</p>

                    <form method="POST" action="{{ route('evidence.custody.store', $evidence) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf

                        <div>
                            <x-input-label for="disposition_event" :value="__('Event')" />
                            <select id="disposition_event" name="event_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                <option value="release">{{ __('Release') }}</option>
                                <option value="disposal">{{ __('Disposal') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="disposition_occurred_at" :value="__('Occurred at')" />
                            <x-text-input id="disposition_occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full" :value="old('occurred_at', now()->format('Y-m-d\TH:i'))" required />
                            <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="disposition_notes" :value="__('Notes')" />
                            <textarea id="disposition_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-primary-button>{{ __('Record disposition') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Chain of custody') }}</h4>

                @if ($evidence->custodyLogs->isEmpty())
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ __('No custody events have been recorded.') }}</p>
                @else
                    <ol class="mt-4 space-y-3">
                        @foreach ($evidence->custodyLogs as $log)
                            <li class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ \Illuminate\Support\Str::headline($log->event_type->value) }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $log->occurred_at?->format('M d, Y h:i A') }}</p>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $log->transferredBy?->name ?? '—' }} @if ($log->receivedBy) &rarr; {{ $log->receivedBy->name }} @endif</p>
                                </div>
                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ $log->from_location ?: '—' }} @if ($log->to_location) &rarr; {{ $log->to_location }} @endif</p>
                                @if (filled($log->notes))
                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $log->notes }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div>
                <a href="{{ route('cases.show', $evidence->case) }}" class="text-sm text-gray-600 underline dark:text-gray-400">{{ __('Back to case details') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
