<?php

namespace App\Http\Requests;

use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;

class UploadEvidenceFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evidence = $this->route('evidence');

        return $evidence instanceof Evidence
            && ($this->user()?->can('update', $evidence) ?? false);
    }

    public function rules(): array
    {
        return [
            'attachment' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,txt,csv,jpg,jpeg,png,webp',
                'extensions:pdf,doc,docx,txt,csv,jpg,jpeg,png,webp',
                'max:20480',
            ],
        ];
    }
}
