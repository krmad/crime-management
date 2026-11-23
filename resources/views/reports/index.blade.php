<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-base font-semibold text-ink">{{ __('Reports') }}</h2>
    </x-slot>

    <x-page-container class="print:p-0">
        <div class="print:hidden">
            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Reports') }}</h1>
            <p class="mt-1 text-sm text-muted">Case totals and breakdowns for the selected reporting period.</p>
        </div>

        <x-card title="Report filters" subtitle="Narrow the reporting period and scope" class="print:hidden">
            <form method="GET" action="{{ route('reports.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="old('from', $filters['from'])" />
                    <x-input-error :messages="$errors->get('from')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="old('to', $filters['to'])" />
                    <x-input-error :messages="$errors->get('to')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <x-select-input id="status" name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\CaseStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="classification" :value="__('Classification')" />
                    <x-select-input id="classification" name="classification">
                        <option value="">{{ __('All classifications') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $filters['classification'] === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('classification')" class="mt-2" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-secondary-button type="submit">{{ __('Apply filters') }}</x-secondary-button>
                    <a href="{{ route('reports.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2.5 text-sm font-semibold text-ink shadow-sm transition hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">{{ __('Export CSV') }}</a>
                    <button type="button" onclick="window.print()" class="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2.5 text-sm font-semibold text-ink shadow-sm transition hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">{{ __('Print') }}</button>
                </div>
            </form>
        </x-card>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 print:grid-cols-5 print:gap-3" aria-label="Case statistics">
            @foreach ($summary as $index => $statistic)
                <x-stat-card
                    :label="$statistic['label']"
                    :value="$statistic['value']"
                    :tone="['brand', 'warning', 'brand', 'violet', 'success'][$index] ?? 'brand'"
                    :icon="['cases', 'clock', 'search', 'scale', 'check'][$index] ?? 'cases'"
                    class="print:shadow-none"
                />
            @endforeach
        </section>

        @if ($cases->isEmpty())
            <x-card class="print:shadow-none">
                <x-empty-state title="No cases found" icon="cases">
                    <x-slot name="description">{{ __('No cases match the selected filters.') }}</x-slot>
                </x-empty-state>
            </x-card>
        @else
            <div class="grid gap-6 xl:grid-cols-2 print:grid-cols-2 print:gap-4">
                <x-card title="Cases by status" class="print:shadow-none print:break-inside-avoid">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm print:text-xs">
                            <thead class="bg-canvas text-xs uppercase tracking-wide text-muted">
                                <tr><th class="py-2 font-semibold">{{ __('Status') }}</th><th class="py-2 text-end font-semibold">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach (\App\Enums\CaseStatus::cases() as $statusOption)
                                    <tr>
                                        <td class="py-2 text-muted">{{ \Illuminate\Support\Str::headline($statusOption->value) }}</td>
                                        <td class="py-2 text-end font-semibold text-ink">{{ $statusCounts->get($statusOption->value, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Case outcomes" class="print:shadow-none print:break-inside-avoid">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm print:text-xs">
                            <tbody class="divide-y divide-line">
                                @foreach ($outcomes as $outcome)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $outcome['label'] }}</td>
                                        <td class="py-2 text-end font-semibold text-ink">{{ $outcome['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Cases by crime type" class="print:shadow-none print:break-inside-avoid">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm print:text-xs">
                            <thead class="bg-canvas text-xs uppercase tracking-wide text-muted">
                                <tr><th class="py-2 font-semibold">{{ __('Crime type') }}</th><th class="py-2 text-end font-semibold">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($byCrimeType as $row)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $row['label'] }}</td>
                                        <td class="py-2 text-end font-semibold text-ink">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Cases by classification" class="print:shadow-none print:break-inside-avoid">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm print:text-xs">
                            <thead class="bg-canvas text-xs uppercase tracking-wide text-muted">
                                <tr><th class="py-2 font-semibold">{{ __('Classification') }}</th><th class="py-2 text-end font-semibold">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($byClassification as $row)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $row['label'] }}</td>
                                        <td class="py-2 text-end font-semibold text-ink">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Monthly case registrations" class="print:shadow-none print:break-inside-avoid">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm print:text-xs">
                            <thead class="bg-canvas text-xs uppercase tracking-wide text-muted">
                                <tr><th class="py-2 font-semibold">{{ __('Month') }}</th><th class="py-2 text-end font-semibold">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($monthly as $row)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $row['month'] }}</td>
                                        <td class="py-2 text-end font-semibold text-ink">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Investigator workload" class="print:shadow-none print:break-inside-avoid">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm print:text-xs">
                            <thead class="bg-canvas text-xs uppercase tracking-wide text-muted">
                                <tr><th class="py-2 font-semibold">{{ __('Investigator') }}</th><th class="py-2 text-end font-semibold">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($workload as $row)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $row['investigator'] }}</td>
                                        <td class="py-2 text-end font-semibold text-ink">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        @endif
    </x-page-container>
</x-app-layout>
