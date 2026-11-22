<?php

namespace App\Http\Requests;

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'badge_number' => ['nullable', 'string', 'max:50', Rule::unique('officer_profiles', 'badge_number')],
            'rank' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:150'],
            'police_station' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
        ];
    }
}
