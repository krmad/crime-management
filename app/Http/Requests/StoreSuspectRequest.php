<?php

namespace App\Http\Requests;

use App\Models\Suspect;
use Illuminate\Foundation\Http\FormRequest;

class StoreSuspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Suspect::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
