<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CommissionSetting;
use Illuminate\Http\Request;

class CommissionSettingController extends Controller
{
    public function edit()
    {
        $defaults = config('commission.roles', []);
        $rolePercents = CommissionSetting::resolveRolePercents();

        $roleMeta = [
            'acquirer' => [
                'label' => 'جذب‌کننده',
                'description' => 'نقش جذب سرنخ یا فرصت',
            ],
            'relationship_owner' => [
                'label' => 'مالک فرصت',
                'description' => 'مسئول اصلی ارتباط با مشتری',
            ],
            'closer' => [
                'label' => 'نهایی‌کننده',
                'description' => 'نقشی که قرارداد را نهایی می‌کند',
            ],
            'execution_owner' => [
                'label' => 'پشتیبان فنی',
                'description' => 'مسئول اجرای فنی یا تحویل',
            ],
        ];

        $roleMeta = array_intersect_key($roleMeta, $defaults);

        return view('settings.commissions', compact('rolePercents', 'roleMeta'));
    }

    public function update(Request $request)
    {
        $defaults = config('commission.roles', []);
        $roleKeys = array_keys($defaults);

        $rules = [];
        foreach ($roleKeys as $key) {
            $rules["role_percents.$key"] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }

        $validated = $request->validate($rules);
        $inputPercents = $validated['role_percents'] ?? [];

        $normalized = [];
        foreach ($roleKeys as $key) {
            $value = $inputPercents[$key] ?? $defaults[$key] ?? 0;
            if ($value === '' || $value === null) {
                $value = $defaults[$key] ?? 0;
            }
            $normalized[$key] = (float) $value;
        }

        $settings = CommissionSetting::query()->firstOrCreate([]);
        $settings->role_percents = $normalized;
        $settings->save();

        return redirect()
            ->route('settings.commissions.edit')
            ->with('status', 'درصد کمیسیون‌ها با موفقیت ذخیره شد.');
    }
}
