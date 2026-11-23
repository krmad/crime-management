<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Evidence') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (in_array(session('status'), ['evidence-created', 'evidence-updated', 'evidence-file-uploaded', 'custody-recorded'], true))
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Evidence record updated.') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="GET" action="{{ route('evidence.index') }}" class="grid gap-4 sm:grid-cols-[1fr_180px_180px_auto] sm:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="old('search', $search)" placeholder="{{ __('Reference or title') }}" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Enums\EvidenceStatus::cases() as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All types') }}</option>
                            @foreach (\App\Enums\EvidenceType::cases() as $typeOption)
                                <option value="{{ $typeOption->value }}" @selected($type === $typeOption->value)>{{ \Illuminate\Support\Str::headline($typeOption->value) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-secondary-button>{{ __('Filter') }}</x-secondary-button>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                @if ($evidence->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No evidence matches the selected filters.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Case') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Collected') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Storage location') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($evidence as $item)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $item->evidence_number }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            <span class="block font-medium">{{ $item->title }}</span>
                                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($item->evidence_type->value) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            <a href="{{ route('cases.show', $item->case) }}" class="text-indigo-600 underline dark:text-indigo-400">{{ $item->case->title }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-400">{{ $item->collected_at?->format('M d, Y') }}<span class="mt-0.5 block text-xs">{{ $item->collector?->name ?? '—' }}</span></td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($item->status->value) }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $item->storage_location ?: '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <a href="{{ route('evidence.show', $item) }}" class="text-indigo-600 underline dark:text-indigo-400">{{ __('View') }}</a>
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

                    <div class="mt-4">
                        {{ $evidence->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
