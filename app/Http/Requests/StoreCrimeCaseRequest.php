<?php

namespace App\Http\Requests;

use App\Enums\CaseTitleType;
use App\Models\CrimeCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrimeCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', CrimeCase::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_title_type' => ['required', Rule::enum(CaseTitleType::class)],
            'custom_crime_type' => [
                'required_if:case_title_type,'.CaseTitleType::Other->value,
                'prohibited_unless:case_title_type,'.CaseTitleType::Other->value,
                'string',
                'max:255',
            ],
            'level_1_category_id' => [
                'required',
                'integer',
                Rule::exists('crime_categories', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->where('level', 1)
                    ->whereNull('parent_id')),
            ],
            'level_2_category_id' => [
                'required',
                'integer',
                Rule::exists('crime_categories', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->where('level', 2)
                    ->where('parent_id', $this->input('level_1_category_id'))),
            ],
            'crime_category_id' => [
                'required',
                'integer',
                Rule::exists('crime_categories', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->where('level', 3)
                    ->where('parent_id', $this->input('level_2_category_id'))),
            ],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'case_title_type' => 'case title / type',
            'custom_crime_type' => 'custom crime type',
            'level_1_category_id' => 'level 1 category',
            'level_2_category_id' => 'level 2 category',
            'crime_category_id' => 'level 3 category',
        ];
    }
}
