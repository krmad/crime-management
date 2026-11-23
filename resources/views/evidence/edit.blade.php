<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit evidence metadata') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $evidence->evidence_number }}</p>

                <form method="POST" action="{{ route('evidence.update', $evidence) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $evidence->title)" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="evidence_type" :value="__('Evidence type')" />
                        <select id="evidence_type" name="evidence_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                            @foreach (\App\Enums\EvidenceType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('evidence_type', $evidence->evidence_type->value) === $type->value)>{{ \Illuminate\Support\Str::headline($type->value) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('evidence_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('description', $evidence->description) }}</textarea>
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
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                            @foreach ([\App\Enums\EvidenceStatus::Collected, \App\Enums\EvidenceStatus::Stored, \App\Enums\EvidenceStatus::Released, \App\Enums\EvidenceStatus::Disposed] as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $evidence->status->value) === $status->value)>{{ \Illuminate\Support\Str::headline($status->value) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save evidence') }}</x-primary-button>
                        <a href="{{ route('evidence.show', $evidence) }}" class="text-sm text-gray-600 underline dark:text-gray-400">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
