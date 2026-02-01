<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Helpers\FormOptionsHelper;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;

class LeadSettingsController extends Controller
{
    public function edit()
    {
        $users = User::orderBy('name')->get();
        $leadSources = FormOptionsHelper::leadSources();
        $companySources = FormOptionsHelper::companyLeadSources();
        $companyAcquirerUserId = FormOptionsHelper::resolveCompanyAcquirerUserId();

        return view('settings.sales.leads', compact(
            'users',
            'leadSources',
            'companySources',
            'companyAcquirerUserId'
        ));
    }

    public function update(Request $request)
    {
        $leadSources = array_keys(FormOptionsHelper::leadSources());

        $validated = $request->validate([
            'company_acquirer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_sources' => ['nullable', 'array'],
            'company_sources.*' => ['string', 'in:' . implode(',', $leadSources)],
        ]);

        $companySources = $validated['company_sources'] ?? [];

        AppSetting::setValue('lead.company_acquirer_user_id', $validated['company_acquirer_user_id'] ?? null);
        AppSetting::setValue('lead.company_sources', json_encode(array_values($companySources)));

        return back()->with('status', 'تنظیمات سرنخ‌ها با موفقیت ذخیره شد.');
    }
}
