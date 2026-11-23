<?php

namespace App\Http\Requests;

use App\Enums\CustodyEvent;
use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustodyEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evidence = $this->route('evidence');
        $eventType = CustodyEvent::tryFrom((string) $this->input('event_type'));

        if (! $evidence instanceof Evidence || $eventType === null) {
            return false;
        }

        if (in_array($eventType, [CustodyEvent::Release, CustodyEvent::Disposal], true)) {
            return $this->user()?->can('dispose', $evidence) ?? false;
        }

        return $this->user()?->can('update', $evidence) ?? false;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::enum(CustodyEvent::class)->except([CustodyEvent::Collection])],
            'received_by' => ['nullable', 'exists:users,id', 'required_if:event_type,transfer'],
            'to_location' => ['nullable', 'string', 'max:255', 'required_if:event_type,transfer,location_change'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
