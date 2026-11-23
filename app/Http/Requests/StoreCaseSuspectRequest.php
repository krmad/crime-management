<?php

namespace App\Http\Requests;

use App\Enums\SuspectCaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseSuspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePeople', $this->route('case')) ?? false;
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
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(SuspectCaseStatus::class)],
        ];
    }
}
