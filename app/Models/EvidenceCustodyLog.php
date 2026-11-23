<?php

namespace App\Models;

use App\Enums\CustodyEvent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'evidence_id',
    'event_type',
    'transferred_by',
    'received_by',
    'from_location',
    'to_location',
    'occurred_at',
    'notes',
])]
class EvidenceCustodyLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_type' => CustodyEvent::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Custody events are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Custody events are append-only.');
        });
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class, 'evidence_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
