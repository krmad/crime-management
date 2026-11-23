<?php

namespace App\Http\Requests;

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Models\CrimeCase;
use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        if (! $case instanceof CrimeCase) {
            return false;
        }

        return $this->user()?->can('create', [Evidence::class, $case]) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'evidence_type' => ['required', Rule::enum(EvidenceType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'collected_at' => ['required', 'date', 'before_or_equal:now'],
            'collected_by' => ['nullable', 'exists:users,id'],
            'storage_location' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EvidenceStatus::class)->only([EvidenceStatus::Collected, EvidenceStatus::Stored])],
        ];
    }
}
