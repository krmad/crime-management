<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Cases') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'case-created')
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('Case created.') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Case records') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Cases visible to your role.') }}</p>
                    </div>

                    @can('create', App\Models\CrimeCase::class)
                        <a href="{{ route('cases.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            {{ __('Create case') }}
                        </a>
                    @endcan
                </div>

                @if ($cases->isEmpty())
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No cases are available.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Crime type') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Location') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Incident date') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Creator') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Investigator') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($cases as $case)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $case->title }}</td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $case->crime_type }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $case->location }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $case->status->value }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $case->incident_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $case->creator?->name ?? '—' }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $case->investigator?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $cases->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
