<?php

namespace App\Http\Requests;

use App\Enums\SuspectCaseStatus;
use App\Models\Suspect;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachSuspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePeople', $this->route('case')) ?? false;
    }

    public function rules(): array
    {
        $case = $this->route('case');

        return [
            'suspect_id' => [
                'required',
                'integer',
                Rule::exists('suspects', 'id'),
                Rule::unique('case_suspect', 'suspect_id')->where(fn (Builder $query): Builder => $query->where('case_id', $case->getKey())),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->user() !== null && Suspect::visibleTo($this->user())->whereKey($value)->doesntExist()) {
                        $fail('The selected suspect is not available.');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(SuspectCaseStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'suspect_id.unique' => 'This suspect is already linked to this case.',
        ];
    }
}
