<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderWorkflowSetting;
use App\Models\User;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $poSettings = PurchaseOrderWorkflowSetting::first();
        if (! $poSettings) {
            $poSettings = new PurchaseOrderWorkflowSetting;
        }

        // Stages to display on the page
        $poStages = [
            'created' => 'ایجاد شده',
            'supervisor_approval' => 'تایید سرپرست کارخانه',
            'manager_approval' => 'تایید مدیر کل',
            'accounting_approval' => 'تایید حسابداری / پرداخت',
            'purchased' => 'خرید انجام شده',
            'paid' => 'پرداخت‌شده',
        ];

        return view('settings.workflows.index', compact('users', 'poSettings', 'poStages'));
    }

    public function updatePurchaseOrder(Request $request)
    {
        $data = $request->validate([
            'first_approver_id' => ['nullable', 'exists:users,id', 'different:second_approver_id', 'different:accounting_user_id'],
            'second_approver_id' => ['nullable', 'exists:users,id', 'different:first_approver_id', 'different:accounting_user_id'],
            'accounting_user_id' => ['nullable', 'exists:users,id', 'different:first_approver_id', 'different:second_approver_id'],
            'first_approver_substitute' => ['nullable', 'exists:users,id', 'different:first_approver_id'],
            'second_approver_substitute' => ['nullable', 'exists:users,id', 'different:second_approver_id'],
            'accounting_approver_substitute' => ['nullable', 'exists:users,id', 'different:accounting_user_id'],
        ]);

        $settings = PurchaseOrderWorkflowSetting::first();
        if (! $settings) {
            $settings = new PurchaseOrderWorkflowSetting;
        }

        $settings->fill([
            'first_approver_id' => $data['first_approver_id'] ?? null,
            'second_approver_id' => $data['second_approver_id'] ?? null,
            'accounting_user_id' => $data['accounting_user_id'] ?? null,
            'first_approver_substitute_id' => $request->input('first_approver_substitute') ?? $request->input('first_approver_substitute_id'),
            'second_approver_substitute_id' => $request->input('second_approver_substitute') ?? $request->input('second_approver_substitute_id'),
            'accounting_approver_substitute_id' => $request->input('accounting_approver_substitute') ?? $request->input('accounting_approver_substitute_id'),
        ]);
        $settings->save();

        return back()->with('success', 'تنظیمات گردش کار سفارش خرید ذخیره شد.');
    }
}
