<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use RuntimeException;

#[Fillable([
    'case_id',
    'evidence_number',
    'title',
    'evidence_type',
    'description',
    'collected_at',
    'collected_by',
    'storage_location',
    'status',
    'private_file_path',
    'original_filename',
    'mime_type',
    'file_size',
])]
class Evidence extends Model
{
    use HasFactory;

    protected $table = 'evidence';

    protected function casts(): array
    {
        return [
            'evidence_type' => EvidenceType::class,
            'status' => EvidenceStatus::class,
            'collected_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can('view-all-cases')) {
            return;
        }

        if ($user->cannot('investigate-cases') && $user->cannot('open-cases')) {
            $query->whereKey(null);

            return;
        }

        $query->whereHas('case', fn (Builder $caseQuery): Builder => $caseQuery->visibleTo($user));
    }

    public static function generateEvidenceNumber(): string
    {
        return sprintf('EV-%d-%s', now()->year, Str::upper(Str::random(8)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createWithReference(array $attributes): self
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return static::create([
                    ...$attributes,
                    'evidence_number' => self::generateEvidenceNumber(),
                ]);
            } catch (QueryException $exception) {
                if (! str_contains($exception->getMessage(), 'evidence_evidence_number_unique')) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to generate a unique evidence number.');
    }

    public function formattedFileSize(): string
    {
        $bytes = $this->file_size ?? 0;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CrimeCase::class, 'case_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function custodyLogs(): HasMany
    {
        return $this->hasMany(EvidenceCustodyLog::class, 'evidence_id')
            ->orderBy('occurred_at')
            ->orderBy('id');
    }
}
