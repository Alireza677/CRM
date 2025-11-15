<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AfterSalesServiceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'coordinator_mobile' => $this->normalizeDigits($this->input('coordinator_mobile')),
        ]);
    }

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'coordinator_name' => ['required', 'string', 'max:255'],
            'coordinator_mobile' => ['required', 'string', 'max:32', 'regex:/^(\+98|0)?9\d{9}$/'],
            'issue_description' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'نام مشتری',
            'address' => 'آدرس',
            'coordinator_name' => 'مسئول هماهنگ‌کننده',
            'coordinator_mobile' => 'شماره همراه هماهنگ‌کننده',
            'issue_description' => 'شرح مشکل دستگاه',
        ];
    }

    private function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];

        return str_replace($ar, $en, str_replace($fa, $en, $value));
    }
}
