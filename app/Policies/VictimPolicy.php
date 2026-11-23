<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Victim;

class VictimPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Administrator, UserRole::PoliceOfficer, UserRole::Investigator], true);
    }

    public function view(User $user, Victim $victim): bool
    {
        if ($user->can('view-all-cases')) {
            return true;
        }

        return $victim->cases()->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Administrator;
    }

    public function update(User $user, Victim $victim): bool
    {
        if ($user->can('view-all-cases')) {
            return true;
        }

        $associatedCases = $victim->cases();
        $totalCases = $associatedCases->count();

        return $totalCases > 0 && $associatedCases->visibleTo($user)->count() === $totalCases;
    }

    public function delete(User $user, Victim $victim): bool
    {
        return false;
    }
}
