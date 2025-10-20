<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ReportQueryRule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required','string','max:190'],
            'description' => ['nullable','string'],
            'model' => ['nullable','string','max:190'],
            'query_json' => ['nullable', new ReportQueryRule()],
            'visibility' => ['required','in:private,public,shared'],
            'shared_user_ids' => ['nullable','array'],
            'shared_user_ids.*' => ['integer','exists:users,id'],
            'shared_can_edit_ids' => ['nullable','array'],
            'shared_can_edit_ids.*' => ['integer','exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $qj = $this->input('query_json');
        if (is_string($qj)) {
            $decoded = json_decode($qj, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['query_json' => $decoded]);
            }
        }
    }
}
