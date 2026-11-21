<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignInvestigatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        return [
            'investigator_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Investigator->value),
            ],
        ];
    }
}
