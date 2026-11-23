<?php

namespace App\Http\Requests;

use App\Enums\SuspectCaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseSuspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePeople', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(SuspectCaseStatus::class)],
        ];
    }
}
