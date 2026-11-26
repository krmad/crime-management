<?php

namespace App\Models;

use App\Enums\PunishmentType;
use Database\Factories\PunishmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'case_judgment_id',
    'type',
    'fine_amount',
    'imprisonment_term',
    'details',
])]
class Punishment extends Model
{
    /** @use HasFactory<PunishmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => PunishmentType::class,
            'fine_amount' => 'decimal:2',
        ];
    }

    public function judgment(): BelongsTo
    {
        return $this->belongsTo(CaseJudgment::class, 'case_judgment_id');
    }
}
