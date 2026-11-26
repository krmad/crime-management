<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Database\Factories\OfficerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'badge_number', 'rank', 'department', 'police_station', 'phone', 'employment_status'])]
class OfficerProfile extends Model
{
    /** @use HasFactory<OfficerProfileFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
