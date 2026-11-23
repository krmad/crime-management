<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Evidence') }}</h2>
    </x-slot>

    <x-page-container>
        @if (in_array(session('status'), ['evidence-created', 'evidence-updated', 'evidence-file-uploaded', 'custody-recorded'], true))
            <x-alert tone="success">{{ __('Evidence record updated.') }}</x-alert>
        @endif

        <x-card title="Filter evidence" subtitle="Search by reference, title, status, or type">
            <form method="GET" action="{{ route('evidence.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,1fr)_auto] xl:items-end">
                <div>
                    <x-input-label for="search" :value="__('Search')" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="old('search', $search)" placeholder="{{ __('Reference or title') }}" />
                </div>

                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <x-select-input id="status" name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\EvidenceStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <div>
                    <x-input-label for="type" :value="__('Type')" />
                    <x-select-input id="type" name="type">
                        <option value="">{{ __('All types') }}</option>
                        @foreach (\App\Enums\EvidenceType::cases() as $typeOption)
                            <option value="{{ $typeOption->value }}" @selected($type === $typeOption->value)>{{ \Illuminate\Support\Str::headline($typeOption->value) }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </form>
        </x-card>

        <x-card title="Evidence register" subtitle="Items visible to your role" :padded="false">
            @if ($evidence->isEmpty())
                <div class="p-5">
                    <x-empty-state title="No evidence found" icon="evidence">
                        <x-slot name="description">{{ __('No evidence matches the selected filters.') }}</x-slot>
                    </x-empty-state>
                </div>
            @else
                <x-table min-width="wide">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Reference') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Title') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Case') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Collected') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Storage location') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($evidence as $item)
                        <tr class="transition-colors hover:bg-canvas">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-brand">{{ $item->evidence_number }}</td>
                            <td class="px-5 py-4 text-ink">
                                <span class="block font-medium">{{ $item->title }}</span>
                                <span class="mt-0.5 block text-xs text-muted">{{ \Illuminate\Support\Str::headline($item->evidence_type->value) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('cases.show', $item->case) }}" class="font-medium text-brand hover:text-brand-dark hover:underline">{{ $item->case->title }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">
                                {{ $item->collected_at?->format('M d, Y') }}
                                <span class="mt-0.5 block text-xs">{{ $item->collector?->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <x-status-badge :status="$item->status->value" />
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $item->storage_location ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('evidence.show', $item) }}" class="text-sm font-semibold text-brand hover:text-brand-dark hover:underline">{{ __('View') }}</a>
                                    @if ($item->private_file_path)
                                        @can('download', $item)
                                            <a href="{{ route('evidence.download', $item) }}" class="text-sm font-semibold text-brand hover:text-brand-dark hover:underline">{{ __('Download') }}</a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <div class="border-t border-line px-5 py-4">
                    {{ $evidence->links() }}
                </div>
            @endif
        </x-card>
    </x-page-container>
</x-app-layout>
