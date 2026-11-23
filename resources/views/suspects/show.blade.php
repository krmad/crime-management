<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Suspect details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $suspect->full_name }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Profile details shared across linked cases.') }}</p>
                    </div>

                    @can('update', $suspect)
                        <a href="{{ route('suspects.edit', $suspect) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            {{ __('Edit suspect') }}
                        </a>
                    @endcan
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Alias') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $suspect->alias ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Date of birth') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $suspect->date_of_birth?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Gender') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $suspect->gender ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $suspect->phone ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Address') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $suspect->address ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Linked cases') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Only cases available to your role are shown.') }}</p>

                @if ($cases->isEmpty())
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No visible cases are linked to this suspect.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Case') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Suspect status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($cases as $case)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-4 py-4">
                                            <a href="{{ route('cases.show', $case) }}" class="text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ $case->title }}</a>
                                        </td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $case->status->value }}</td>
                                        <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::headline($case->pivot->status?->value ?? '') }}</td>
                                        <td class="px-4 py-4 text-gray-600 dark:text-gray-400">{{ $case->pivot->notes ?? '—' }}</td>
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
