<?php

namespace App\Http\Requests;

use App\Enums\CaseStatus;
use App\Models\CrimeCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CrimeCase::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'before_or_equal:to'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::enum(CaseStatus::class)],
            'classification' => ['nullable', 'exists:crime_categories,id'],
        ];
    }
}
