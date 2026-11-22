<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Database\Factories\CrimeCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'crime_type',
    'location',
    'description',
    'status',
    'incident_date',
    'crime_category_id',
    'custom_crime_type',
])]
class CrimeCase extends Model
{
    /** @use HasFactory<CrimeCaseFactory> */
    use HasFactory;

    protected $table = 'cases';

    protected $attributes = [
        'status' => CaseStatus::Open->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'incident_date' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->cannot('view-all-cases') && $user->can('investigate-cases')) {
            $query->whereBelongsTo($user, 'investigator');
        } elseif ($user->cannot('view-all-cases') && $user->can('open-cases')) {
            $query->whereBelongsTo($user, 'creator');
        }
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }

    public function crimeCategory(): BelongsTo
    {
        return $this->belongsTo(CrimeCategory::class, 'crime_category_id');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function classification(): array
    {
        $category = $this->crimeCategory;

        if ($category === null) {
            return [];
        }

        return collect([$category->parent?->parent, $category->parent, $category])
            ->filter()
            ->map(fn (CrimeCategory $ancestor): array => [
                'id' => $ancestor->getKey(),
                'name' => $ancestor->name,
            ])
            ->values()
            ->all();
    }
}
