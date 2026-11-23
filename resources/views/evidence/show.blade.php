<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Evidence details') }}</h2>
    </x-slot>

    <x-page-container>
        @if (in_array(session('status'), ['evidence-updated', 'evidence-file-uploaded', 'custody-recorded'], true))
            <x-alert tone="success">{{ __('Evidence record updated.') }}</x-alert>
        @endif

        <x-card title="Evidence overview" subtitle="Registered item and current custody state" :padded="false">
            <div class="p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $evidence->evidence_number }}</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-ink">{{ $evidence->title }}</h3>
                        <a href="{{ route('cases.show', $evidence->case) }}" class="mt-1 inline-block text-sm font-semibold text-brand hover:text-brand-dark hover:underline">{{ $evidence->case->title }}</a>
                    </div>

                    <x-status-badge :status="$evidence->status->value" />
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ \Illuminate\Support\Str::headline($evidence->evidence_type->value) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Collected at') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $evidence->collected_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Collected by') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $evidence->collector?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Storage location') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $evidence->storage_location ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Description') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-ink">{{ $evidence->description ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Supporting file') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $evidence->original_filename ?: __('No file attached.') }}</dd>
                        @if ($evidence->mime_type)
                            <dd class="mt-0.5 text-xs text-muted">{{ $evidence->mime_type }} @if ($evidence->file_size) &middot; {{ $evidence->formattedFileSize() }} @endif</dd>
                        @endif
                        @if ($evidence->private_file_path)
                            @can('download', $evidence)
                                <a href="{{ route('evidence.download', $evidence) }}" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-brand hover:text-brand-dark hover:underline">
                                    <x-icon name="download" class="h-4 w-4" />
                                    {{ __('Download file') }}
                                </a>
                            @endcan
                        @endif
                    </div>
                </dl>
            </div>

            @can('update', $evidence)
                <div class="flex flex-wrap gap-3 border-t border-line bg-canvas/50 px-5 py-4">
                    <x-link-button :href="route('evidence.edit', $evidence)" tone="secondary">
                        <x-icon name="file" class="h-4 w-4" />
                        {{ __('Edit metadata') }}
                    </x-link-button>
                </div>
            @endcan
        </x-card>

        @can('update', $evidence)
            <x-card title="Attach supporting file" subtitle="Files are stored in private storage and require authorization to download.">
                <form method="POST" action="{{ route('evidence.file.store', $evidence) }}" enctype="multipart/form-data" class="max-w-xl space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="attachment" :value="__('File')" />
                        <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm text-muted file:me-3 file:rounded-lg file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-950 hover:file:bg-brand/90" required />
                        <p class="mt-1 text-xs text-muted">{{ __('Allowed: PDF, Word, text, CSV, JPG, PNG, WebP. Maximum 20 MB.') }}</p>
                        <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Upload file') }}</x-primary-button>
                </form>
            </x-card>
        @endcan

        @can('update', $evidence)
            <x-card title="Record custody event" subtitle="Custody events are permanent and cannot be edited or deleted.">
                <form method="POST" action="{{ route('evidence.custody.store', $evidence) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-input-label for="event_type" :value="__('Event')" />
                        <x-select-input id="event_type" name="event_type" required>
                            <option value="transfer">{{ __('Transfer') }}</option>
                            <option value="location_change">{{ __('Storage location change') }}</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="received_by" :value="__('Received by')" />
                        <x-select-input id="received_by" name="received_by">
                            <option value="">{{ __('Select a user') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('received_by') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </x-select-input>
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
                        <x-textarea-input id="notes" name="notes" rows="2">{{ old('notes') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>{{ __('Record event') }}</x-primary-button>
                    </div>
                </form>
            </x-card>
        @endcan

        @can('dispose', $evidence)
            <x-card title="Release or disposal" subtitle="Only administrators may record release or disposal events.">
                <form method="POST" action="{{ route('evidence.custody.store', $evidence) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-input-label for="disposition_event" :value="__('Event')" />
                        <x-select-input id="disposition_event" name="event_type" required>
                            <option value="release">{{ __('Release') }}</option>
                            <option value="disposal">{{ __('Disposal') }}</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="disposition_occurred_at" :value="__('Occurred at')" />
                        <x-text-input id="disposition_occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full" :value="old('occurred_at', now()->format('Y-m-d\TH:i'))" required />
                        <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="disposition_notes" :value="__('Notes')" />
                        <x-textarea-input id="disposition_notes" name="notes" rows="2">{{ old('notes') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>{{ __('Record disposition') }}</x-primary-button>
                    </div>
                </form>
            </x-card>
        @endcan

        <x-card title="Chain of custody" :padded="false">
            @if ($evidence->custodyLogs->isEmpty())
                <div class="p-5">
                    <x-empty-state title="No custody events" icon="clipboard">
                        <x-slot name="description">{{ __('No custody events have been recorded.') }}</x-slot>
                    </x-empty-state>
                </div>
            @else
                <ol class="divide-y divide-line">
                    @foreach ($evidence->custodyLogs as $log)
                        <li class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <x-status-badge :status="$log->event_type->value" />
                                    <p class="mt-2 text-xs text-muted">{{ $log->occurred_at?->format('M d, Y h:i A') }}</p>
                                </div>
                                <p class="text-sm text-muted">{{ $log->transferredBy?->name ?? '—' }} @if ($log->receivedBy) &rarr; {{ $log->receivedBy->name }} @endif</p>
                            </div>
                            <p class="mt-2 rounded-lg bg-canvas px-3 py-2 text-xs text-muted">{{ $log->from_location ?: '—' }} @if ($log->to_location) &rarr; {{ $log->to_location }} @endif</p>
                            @if (filled($log->notes))
                                <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $log->notes }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>

        <div>
            <a href="{{ route('cases.show', $evidence->case) }}" class="text-sm font-semibold text-muted hover:text-brand hover:underline">{{ __('Back to case details') }}</a>
        </div>
    </x-page-container>
</x-app-layout>
