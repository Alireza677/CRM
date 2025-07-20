<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AutomationRule;
use App\Models\User;

class AutomationController extends Controller
{
    public function edit()
    {
        $rules = AutomationRule::with('approvers')->get(); // همه قوانین
        $rule = $rules->first();                           // قانون اول برای ویرایش

        $users = User::all();
        $stages = ['ارسال برای تاییدیه', 'تایید شده', 'رد شده'];

        return view('settings.automation.index', compact('rule', 'rules', 'users', 'stages'));
    }


    public function update(Request $request)
    {
        $data = $request->validate([
            'operator' => 'required|in:=,!=',
            'value' => 'required|string',
            'approvers' => 'required|array|min:1',
            'approvers.*' => 'required|exists:users,id',
        ]);
    
        // حذف رکوردها به‌جای truncate
        \DB::table('automation_rule_approvers')->delete();
        \DB::table('automation_rules')->delete();
    
        $rule = AutomationRule::create([
            'proforma_stage' => 'send_for_approval',
            'operator' => $data['operator'],
            'value' => $data['value'],
        ]);
    
        $syncData = [];
    
        if (!empty($data['approvers'][0])) {
            $syncData[$data['approvers'][0]] = ['role' => 'approver_1'];
        }
    
        if (!empty($data['approvers'][1])) {
            $syncData[$data['approvers'][1]] = ['role' => 'approver_2'];
        }
    
        $rule->approvers()->sync($syncData);
    
        return back()->with('success', 'تنظیمات با موفقیت ذخیره شد.');
    }
    
    public function destroyAll()
    {
        \DB::table('automation_rule_approvers')->delete();
        \DB::table('automation_rules')->delete();
    
        return back()->with('success', 'تمام قوانین با موفقیت حذف شدند.');
    }
    



}
