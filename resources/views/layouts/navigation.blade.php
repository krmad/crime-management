<div>
    <div x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
        style="display: none;"
        @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-[250px] flex-col border-r border-line bg-surface shadow-card transition-transform duration-200 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-line px-5">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center text-brand" @click="sidebarOpen = false">
                <x-application-logo class="h-9 w-auto" />
            </a>

            <button type="button" class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted transition hover:bg-canvas hover:text-ink lg:hidden" @click="sidebarOpen = false" aria-label="Close navigation">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Primary navigation">
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard" @click="sidebarOpen = false">
                {{ __('Dashboard') }}
            </x-nav-link>

            @can('create', App\Models\CrimeCase::class)
                <x-nav-link :href="route('cases.create')" :active="request()->routeIs('cases.create')" icon="plus" @click="sidebarOpen = false">
                    {{ __('Crime Reports') }}
                </x-nav-link>
            @endcan

            @can('viewAny', App\Models\CrimeCase::class)
                <x-nav-link :href="route('cases.index')" :active="request()->routeIs('cases.index', 'cases.show')" icon="cases" @click="sidebarOpen = false">
                    {{ __('Cases') }}
                </x-nav-link>
            @endcan

            @can('manage-users')
                <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users" @click="sidebarOpen = false">
                    {{ __('User Management') }}
                </x-nav-link>
                <x-nav-link :href="route('officers.index')" :active="request()->routeIs('officers.*')" icon="officer" @click="sidebarOpen = false">
                    {{ __('Officers') }}
                </x-nav-link>
            @endcan

            @can('viewAny', App\Models\Suspect::class)
                <x-nav-link :href="route('suspects.index')" :active="request()->routeIs('suspects.*')" icon="suspect" @click="sidebarOpen = false">
                    {{ __('Suspects') }}
                </x-nav-link>
                <x-nav-link :href="route('victims.index')" :active="request()->routeIs('victims.*')" icon="victim" @click="sidebarOpen = false">
                    {{ __('Victims') }}
                </x-nav-link>
            @endcan

            @can('viewAny', App\Models\Evidence::class)
                <x-nav-link :href="route('evidence.index')" :active="request()->routeIs('evidence.*', 'cases.evidence.*')" icon="evidence" @click="sidebarOpen = false">
                    {{ __('Evidence') }}
                </x-nav-link>
            @endcan

            @can('viewAny', App\Models\CrimeCase::class)
                <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="reports" @click="sidebarOpen = false">
                    {{ __('Reports') }}
                </x-nav-link>
            @endcan
        </nav>

        <div class="shrink-0 border-t border-line px-5 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">Authorized personnel only</p>
            <p class="mt-1 text-xs text-muted">{{ \Illuminate\Support\Str::headline(Auth::user()->role?->value ?? 'user') }} access</p>
        </div>
    </aside>

</div>
