<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordJudgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordJudgment', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        return [
            'judgment_notes' => ['required', 'string', 'max:5000'],
        ];
    }
}
