<?php

namespace App\Models;

use App\Enums\SuspectCaseStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table(incrementing: true)]
class CaseSuspect extends Pivot
{
    protected $table = 'case_suspect';

    protected $fillable = ['case_id', 'suspect_id', 'notes', 'status'];

    protected function casts(): array
    {
        return [
            'status' => SuspectCaseStatus::class,
        ];
    }
}
