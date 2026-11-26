<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Database\Factories\CaseStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Guarded(['*'])]
class CaseStatusHistory extends Model
{
    /** @use HasFactory<CaseStatusHistoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'previous_status' => CaseStatus::class,
            'new_status' => CaseStatus::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Status history records are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Status history records are append-only.');
        });
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CrimeCase::class, 'case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
