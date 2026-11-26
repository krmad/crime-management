<?php

namespace App\Models;

use App\Enums\CaseJudgmentState;
use App\Enums\JudgmentVerdict;
use Database\Factories\CaseJudgmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'case_id',
    'judge_id',
    'judgment_state',
    'verdict',
    'judgment_notes',
    'acquittal_notes',
    'decided_at',
    'completed_at',
])]
class CaseJudgment extends Model
{
    /** @use HasFactory<CaseJudgmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'judgment_state' => CaseJudgmentState::class,
            'verdict' => JudgmentVerdict::class,
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CrimeCase::class, 'case_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(Punishment::class, 'case_judgment_id');
    }
}
