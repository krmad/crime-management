<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Edit evidence metadata') }}</h2>
    </x-slot>

    <x-page-container>
        <div class="mx-auto max-w-3xl space-y-6">
            <x-card title="Evidence reference" subtitle="Changes to custody details are recorded in the chain of custody">
                <p class="text-sm font-semibold text-brand">{{ $evidence->evidence_number }}</p>
            </x-card>

            <x-card title="Evidence details" subtitle="Update the item's identifying information">
                <form method="POST" action="{{ route('evidence.update', $evidence) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $evidence->title)" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="evidence_type" :value="__('Evidence type')" />
                        <x-select-input id="evidence_type" name="evidence_type" required>
                            @foreach (\App\Enums\EvidenceType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('evidence_type', $evidence->evidence_type->value) === $type->value)>{{ \Illuminate\Support\Str::headline($type->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('evidence_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <x-textarea-input id="description" name="description" rows="4">{{ old('description', $evidence->description) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="collected_at" :value="__('Collected at')" />
                            <x-text-input id="collected_at" name="collected_at" type="datetime-local" class="mt-1 block w-full" :value="old('collected_at', $evidence->collected_at?->format('Y-m-d\TH:i'))" required />
                            <x-input-error :messages="$errors->get('collected_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="storage_location" :value="__('Storage location')" />
                            <x-text-input id="storage_location" name="storage_location" type="text" class="mt-1 block w-full" :value="old('storage_location', $evidence->storage_location)" required />
                            <x-input-error :messages="$errors->get('storage_location')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <x-select-input id="status" name="status" required>
                            @foreach ([\App\Enums\EvidenceStatus::Collected, \App\Enums\EvidenceStatus::Stored, \App\Enums\EvidenceStatus::Released, \App\Enums\EvidenceStatus::Disposed] as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $evidence->status->value) === $status->value)>{{ \Illuminate\Support\Str::headline($status->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex flex-wrap items-center gap-4 border-t border-line pt-5">
                        <x-primary-button>{{ __('Save evidence') }}</x-primary-button>
                        <a href="{{ route('evidence.show', $evidence) }}" class="text-sm font-semibold text-muted hover:text-brand hover:underline">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </x-card>
        </div>
    </x-page-container>
</x-app-layout>
