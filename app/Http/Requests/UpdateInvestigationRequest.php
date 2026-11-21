<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateInvestigation', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        return [
            'investigation_notes' => ['required', 'string', 'max:5000'],
        ];
    }
}
