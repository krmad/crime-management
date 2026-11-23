<?php

namespace App\Http\Requests;

use App\Models\Victim;
use Illuminate\Foundation\Http\FormRequest;

class StoreVictimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Victim::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
