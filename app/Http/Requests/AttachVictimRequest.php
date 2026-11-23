<?php

namespace App\Http\Requests;

use App\Models\Victim;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachVictimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePeople', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        $case = $this->route('case');

        return [
            'victim_id' => [
                'required',
                'integer',
                Rule::exists('victims', 'id'),
                Rule::unique('case_victim', 'victim_id')->where(fn (Builder $query): Builder => $query->where('case_id', $case->getKey())),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->user() !== null && Victim::visibleTo($this->user())->whereKey($value)->doesntExist()) {
                        $fail('The selected victim is not available.');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'victim_id.unique' => 'This victim is already linked to this case.',
        ];
    }
}
