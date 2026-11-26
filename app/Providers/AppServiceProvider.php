<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\Suspect;
use App\Models\User;
use App\Models\Victim;
use App\Policies\CrimeCasePolicy;
use App\Policies\EvidencePolicy;
use App\Policies\SuspectPolicy;
use App\Policies\VictimPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->role === UserRole::Administrator);

        Gate::define('open-cases', fn (User $user): bool => in_array($user->role, [UserRole::Administrator, UserRole::PoliceOfficer], true));

        Gate::define('view-all-cases', fn (User $user): bool => $user->role === UserRole::Administrator);

        Gate::define('investigate-cases', fn (User $user): bool => in_array($user->role, [UserRole::Administrator, UserRole::Investigator], true));

        Gate::define('record-judgment', fn (User $user): bool => $user->role === UserRole::Administrator);

        Gate::define('close-cases', fn (User $user): bool => $user->role === UserRole::Administrator);

        Gate::define('assign-cases', fn (User $user): bool => $user->role === UserRole::Administrator);

        RateLimiter::for('geocoding', fn (Request $request): array => [
            Limit::perSecond(1)->by('nominatim-global'),
            Limit::perMinute(10)->by('nominatim-user-'.($request->user()?->id ?? 'guest')),
        ]);

        Gate::policy(CrimeCase::class, CrimeCasePolicy::class);
        Gate::policy(Evidence::class, EvidencePolicy::class);
        Gate::policy(Suspect::class, SuspectPolicy::class);
        Gate::policy(Victim::class, VictimPolicy::class);
    }
}
