<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Suspect;
use App\Models\User;

class SuspectPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Administrator, UserRole::PoliceOfficer, UserRole::Investigator], true);
    }

    public function view(User $user, Suspect $suspect): bool
    {
        if ($user->can('view-all-cases')) {
            return true;
        }

        return $suspect->cases()->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Administrator;
    }

    public function update(User $user, Suspect $suspect): bool
    {
        if ($user->can('view-all-cases')) {
            return true;
        }

        $associatedCases = $suspect->cases();
        $totalCases = $associatedCases->count();

        return $totalCases > 0 && $associatedCases->visibleTo($user)->count() === $totalCases;
    }

    public function delete(User $user, Suspect $suspect): bool
    {
        return false;
    }
}
