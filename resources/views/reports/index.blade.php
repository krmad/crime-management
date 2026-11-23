<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg print:hidden">
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
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Enums\CaseStatus::cases() as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>{{ \Illuminate\Support\Str::headline($statusOption->value) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="classification" :value="__('Classification')" />
                        <select id="classification" name="classification" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All classifications') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $filters['classification'] === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('classification')" class="mt-2" />
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-secondary-button>{{ __('Apply filters') }}</x-secondary-button>
                        <a href="{{ route('reports.export', request()->query()) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Export CSV') }}</a>
                        <button type="button" onclick="window.print()" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Print') }}</button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($summary as $statistic)
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $statistic['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $statistic['value'] }}</p>
                        <span class="mt-3 block h-2.5 w-2.5 rounded-full {{ $statistic['accent'] }}"></span>
                    </article>
                @endforeach
            </div>

            @if ($cases->isEmpty())
                <div class="rounded-xl border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No cases match the selected filters.') }}</p>
                </div>
            @else
                <div class="grid gap-6 xl:grid-cols-2">
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Cases by status') }}</h3>
                        <table class="mt-4 w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <tr><th class="py-2 font-medium">{{ __('Status') }}</th><th class="py-2 text-end font-medium">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach (\App\Enums\CaseStatus::cases() as $statusOption)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::headline($statusOption->value) }}</td>
                                        <td class="py-2 text-end font-medium text-gray-900 dark:text-white">{{ $statusCounts->get($statusOption->value, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Case outcomes') }}</h3>
                        <table class="mt-4 w-full text-left text-sm">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($outcomes as $outcome)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ $outcome['label'] }}</td>
                                        <td class="py-2 text-end font-medium text-gray-900 dark:text-white">{{ $outcome['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Cases by crime type') }}</h3>
                        <table class="mt-4 w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <tr><th class="py-2 font-medium">{{ __('Crime type') }}</th><th class="py-2 text-end font-medium">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($byCrimeType as $row)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['label'] }}</td>
                                        <td class="py-2 text-end font-medium text-gray-900 dark:text-white">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Cases by classification') }}</h3>
                        <table class="mt-4 w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <tr><th class="py-2 font-medium">{{ __('Classification') }}</th><th class="py-2 text-end font-medium">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($byClassification as $row)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['label'] }}</td>
                                        <td class="py-2 text-end font-medium text-gray-900 dark:text-white">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Monthly case registrations') }}</h3>
                        <table class="mt-4 w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <tr><th class="py-2 font-medium">{{ __('Month') }}</th><th class="py-2 text-end font-medium">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($monthly as $row)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['month'] }}</td>
                                        <td class="py-2 text-end font-medium text-gray-900 dark:text-white">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Investigator workload') }}</h3>
                        <table class="mt-4 w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <tr><th class="py-2 font-medium">{{ __('Investigator') }}</th><th class="py-2 text-end font-medium">{{ __('Cases') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($workload as $row)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['investigator'] }}</td>
                                        <td class="py-2 text-end font-medium text-gray-900 dark:text-white">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
