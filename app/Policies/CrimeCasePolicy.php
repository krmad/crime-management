<?php

namespace App\Policies;

use App\Enums\CaseStatus;
use App\Models\CrimeCase;
use App\Models\User;

class CrimeCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('open-cases') || $user->can('investigate-cases');
    }

    public function view(User $user, CrimeCase $case): bool
    {
        if ($user->can('view-all-cases')) {
            return true;
        }

        if ($user->can('investigate-cases') && $case->investigator_id === $user->id) {
            return true;
        }

        return $user->can('open-cases') && $case->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('open-cases');
    }

    public function update(User $user, CrimeCase $case): bool
    {
        if ($user->can('view-all-cases')) {
            return true;
        }

        if ($user->can('investigate-cases') && $case->investigator_id === $user->id) {
            return true;
        }

        return $user->can('open-cases')
            && $case->created_by === $user->id
            && $case->status === CaseStatus::Open;
    }

    public function assign(User $user, CrimeCase $case): bool
    {
        return $user->can('assign-cases');
    }

    public function recordJudgment(User $user, CrimeCase $case): bool
    {
        return $user->can('record-judgment');
    }

    public function close(User $user, CrimeCase $case): bool
    {
        return $user->can('close-cases');
    }
}
