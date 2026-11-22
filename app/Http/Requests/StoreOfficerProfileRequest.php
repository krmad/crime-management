<?php

namespace App\Http\Requests;

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficerProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query): Builder => $query
                    ->whereIn('role', [UserRole::PoliceOfficer->value, UserRole::Investigator->value])),
                Rule::unique('officer_profiles', 'user_id'),
            ],
            'badge_number' => ['nullable', 'string', 'max:50', Rule::unique('officer_profiles', 'badge_number')],
            'rank' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:150'],
            'police_station' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
        ];
    }
}
