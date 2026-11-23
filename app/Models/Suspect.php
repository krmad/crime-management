<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['full_name', 'alias', 'date_of_birth', 'gender', 'address', 'phone'])]
class Suspect extends Model
{
    use HasFactory;

    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can('view-all-cases')) {
            return;
        }

        if ($user->can('investigate-cases')) {
            $query->whereHas('cases', fn (Builder $caseQuery): Builder => $caseQuery->whereBelongsTo($user, 'investigator'));

            return;
        }

        if ($user->can('open-cases')) {
            $query->whereHas('cases', fn (Builder $caseQuery): Builder => $caseQuery->whereBelongsTo($user, 'creator'));

            return;
        }

        $query->whereKey(null);
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(CrimeCase::class, 'case_suspect', 'suspect_id', 'case_id')
            ->using(CaseSuspect::class)
            ->withPivot(['id', 'notes', 'status'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }
}
