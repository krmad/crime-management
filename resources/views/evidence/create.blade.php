<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Register evidence') }}</h2>
    </x-slot>

    <x-page-container>
        <div class="mx-auto max-w-3xl space-y-6">
            <x-card title="Case" subtitle="The case this item belongs to">
                <p class="text-sm text-muted">{{ __('Case') }}: {{ $case->title }}</p>
            </x-card>

            <x-card title="Evidence details" subtitle="Record the item's identifying information">
                <form method="POST" action="{{ route('cases.evidence.store', $case) }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="evidence_type" :value="__('Evidence type')" />
                        <x-select-input id="evidence_type" name="evidence_type" required>
                            @foreach (\App\Enums\EvidenceType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('evidence_type') === $type->value)>{{ \Illuminate\Support\Str::headline($type->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('evidence_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <x-textarea-input id="description" name="description" rows="4">{{ old('description') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="collected_at" :value="__('Collected at')" />
                            <x-text-input id="collected_at" name="collected_at" type="datetime-local" class="mt-1 block w-full" :value="old('collected_at', now()->format('Y-m-d\TH:i'))" required />
                            <x-input-error :messages="$errors->get('collected_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="storage_location" :value="__('Storage location')" />
                            <x-text-input id="storage_location" name="storage_location" type="text" class="mt-1 block w-full" :value="old('storage_location')" required />
                            <x-input-error :messages="$errors->get('storage_location')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Initial status')" />
                        <x-select-input id="status" name="status" required>
                            @foreach ([\App\Enums\EvidenceStatus::Collected, \App\Enums\EvidenceStatus::Stored] as $status)
                                <option value="{{ $status->value }}" @selected(old('status', \App\Enums\EvidenceStatus::Collected->value) === $status->value)>{{ \Illuminate\Support\Str::headline($status->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex flex-wrap items-center gap-4 border-t border-line pt-5">
                        <x-primary-button>{{ __('Register evidence') }}</x-primary-button>
                        <a href="{{ route('cases.show', $case) }}" class="text-sm font-semibold text-muted hover:text-brand hover:underline">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </x-card>
        </div>
    </x-page-container>
</x-app-layout>
