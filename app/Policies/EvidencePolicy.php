<?php

namespace App\Policies;

use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', CrimeCase::class);
    }

    public function view(User $user, Evidence $evidence): bool
    {
        return $user->can('view', $evidence->case);
    }

    public function create(User $user, CrimeCase $case): bool
    {
        return $user->can('manageEvidence', $case);
    }

    public function update(User $user, Evidence $evidence): bool
    {
        return $user->can('manageEvidence', $evidence->case);
    }

    public function download(User $user, Evidence $evidence): bool
    {
        return $this->view($user, $evidence);
    }

    public function recordCustody(User $user, Evidence $evidence): bool
    {
        return $this->update($user, $evidence);
    }

    public function dispose(User $user, Evidence $evidence): bool
    {
        return $user->can('view-all-cases') && $user->can('manageEvidence', $evidence->case);
    }
}
