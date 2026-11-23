<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table(incrementing: true)]
class CaseVictim extends Pivot
{
    protected $table = 'case_victim';

    protected $fillable = ['case_id', 'victim_id', 'notes'];
}
