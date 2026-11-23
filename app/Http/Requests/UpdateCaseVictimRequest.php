<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseVictimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePeople', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
