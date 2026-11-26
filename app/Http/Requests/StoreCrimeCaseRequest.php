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
    private const UNKNOWN_SUSPECT = 'None';

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
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'description' => ['required', 'string', 'max:10000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:now'],
            'suspect_name' => ['nullable', 'string', 'max:255'],
            'suspect_alias' => [
                'nullable',
                'string',
                'max:255',
                Rule::prohibitedIf(fn (): bool => ! $this->hasKnownSuspect()),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $location = $this->input('location');

        $this->merge([
            'location' => is_string($location) ? trim($location) : $location,
            'latitude' => $this->normalizedCoordinate('latitude'),
            'longitude' => $this->normalizedCoordinate('longitude'),
            'suspect_name' => $this->trimmedNullableString('suspect_name'),
            'suspect_alias' => $this->trimmedNullableString('suspect_alias'),
        ]);
    }

    /**
     * Determine whether the report explicitly says the suspect is unknown.
     */
    public function hasUnknownSuspect(): bool
    {
        $suspectName = $this->input('suspect_name');

        return is_string($suspectName) && strcasecmp($suspectName, self::UNKNOWN_SUSPECT) === 0;
    }

    /**
     * Determine whether a usable suspect name was provided.
     */
    public function hasKnownSuspect(): bool
    {
        return filled($this->input('suspect_name')) && ! $this->hasUnknownSuspect();
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
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'suspect_name' => 'suspect full name',
            'suspect_alias' => 'suspect alias',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'suspect_alias.prohibited' => 'An alias cannot be entered when the suspect is unknown.',
        ];
    }

    private function normalizedCoordinate(string $key): ?string
    {
        $value = $this->input($key);

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function trimmedNullableString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
