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

    public function updateInvestigation(User $user, CrimeCase $case): bool
    {
        if (in_array($case->status, [CaseStatus::JudgmentRecorded, CaseStatus::Closed], true)) {
            return false;
        }

        if ($user->can('view-all-cases')) {
            return true;
        }

        return $user->can('investigate-cases')
            && $case->investigator_id === $user->id;
    }

    public function assign(User $user, CrimeCase $case): bool
    {
        return $user->can('assign-cases')
            && $case->status !== CaseStatus::Closed;
    }

    public function managePeople(User $user, CrimeCase $case): bool
    {
        if ($case->status === CaseStatus::Closed) {
            return false;
        }

        if ($user->can('view-all-cases')) {
            return true;
        }

        if ($user->can('investigate-cases') && $case->investigator_id === $user->id) {
            return true;
        }

        return $user->can('open-cases') && $case->created_by === $user->id;
    }

    public function recordJudgment(User $user, CrimeCase $case): bool
    {
        if ($user->cannot('record-judgment')) {
            return false;
        }

        if ($case->status === CaseStatus::UnderInvestigation) {
            return filled($case->investigation_notes);
        }

        return $case->status === CaseStatus::JudgmentRecorded;
    }

    public function close(User $user, CrimeCase $case): bool
    {
        if ($user->cannot('close-cases')) {
            return false;
        }

        return $case->status === CaseStatus::JudgmentRecorded
            && filled($case->judgment_notes);
    }
}
