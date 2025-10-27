<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PurchaseOrderWorkflowSetting;

class WorkflowController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $poSettings = PurchaseOrderWorkflowSetting::first();
        if (!$poSettings) {
            $poSettings = new PurchaseOrderWorkflowSetting();
        }

        // Stages to display on the page
        $poStages = [
            'created'              => 'ایجاد شده',
            'supervisor_approval'  => 'تایید سرپرست کارخانه',
            'manager_approval'     => 'تایید مدیر کل',
            'accounting_approval'  => 'تایید حسابداری / پرداخت',
            'purchased'            => 'خرید انجام شده',
        ];

        return view('settings.workflows.index', compact('users', 'poSettings', 'poStages'));
    }

    public function updatePurchaseOrder(Request $request)
    {
        $data = $request->validate([
            'first_approver_id'   => ['nullable','exists:users,id','different:second_approver_id','different:accounting_user_id'],
            'second_approver_id'  => ['nullable','exists:users,id','different:first_approver_id','different:accounting_user_id'],
            'accounting_user_id'  => ['nullable','exists:users,id','different:first_approver_id','different:second_approver_id'],
        ]);

        $settings = PurchaseOrderWorkflowSetting::first();
        if (!$settings) {
            $settings = new PurchaseOrderWorkflowSetting();
        }

        $settings->fill($data);
        $settings->save();

        return back()->with('success', 'تنظیمات گردش کار سفارش خرید ذخیره شد.');
    }
}
