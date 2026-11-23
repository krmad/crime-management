<?php

namespace App\Http\Requests;

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evidence = $this->route('evidence');
        $user = $this->user();

        if (! $evidence instanceof Evidence || $user === null || $user->cannot('update', $evidence)) {
            return false;
        }

        $status = EvidenceStatus::tryFrom((string) $this->input('status'));

        if (in_array($status, [EvidenceStatus::Released, EvidenceStatus::Disposed], true)) {
            return $user->can('dispose', $evidence);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'evidence_type' => ['required', Rule::enum(EvidenceType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'collected_at' => ['required', 'date', 'before_or_equal:now'],
            'storage_location' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EvidenceStatus::class)->except([EvidenceStatus::Transferred])],
        ];
    }
}
