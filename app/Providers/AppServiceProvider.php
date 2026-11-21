<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\User;
use App\Policies\CrimeCasePolicy;
use Illuminate\Support\Facades\Gate;
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

        Gate::policy(CrimeCase::class, CrimeCasePolicy::class);
    }
}
