<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Database\Factories\CrimeCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }
}
