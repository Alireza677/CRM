<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AutomationRule;
use App\Models\User;
use App\Models\AutomationRuleApprover; // ← اضافه شد

class AutomationController extends Controller
{
    /**
     * فرم ویرایش تنظیمات اتوماسیون (قانون واحد)
     */
    public function edit()
    {
        // فقط یک قانون نگه می‌داریم
        $rule = AutomationRule::first();

        // مقادیر پیش‌فرض اگر قانونی وجود ندارد
        if (!$rule) {
            $rule = new AutomationRule([
                'proforma_stage'        => 'send_for_approval', // مرحله هدف
                'operator'              => '=',                 // عملگر
                'value'                 => 'send_for_approval', // مقدار مقایسه
                'emergency_approver_id' => null,
            ]);
        }

        // اگر قانون موجود است، approverها را از جدول مربوطه بخوانیم
        $approvers = $rule->exists
            ? AutomationRuleApprover::where('automation_rule_id', $rule->id)->get()
            : collect();

        $approver1Id = optional($approvers->firstWhere('priority', 1))->user_id;
        $approver2Id = optional($approvers->firstWhere('priority', 2))->user_id;

        // برای بایند شدن به فیلدهای فرم با نام‌های فعلی
        $rule->setAttribute('approver_1', $approver1Id);
        $rule->setAttribute('approver_2', $approver2Id);

        // کاربران برای Select
        $users = User::orderBy('name')->get();

        // لیست وضعیت‌ها (کلید = مقدار ذخیره در DB، مقدار = برچسب نمایشی)
        $stages = [
            'draft'              => 'پیش‌نویس',
            'send_for_approval'  => 'ارسال برای تاییدیه',
            'approved'           => 'تایید شده',
            'rejected'           => 'رد شده',
        ];

        return view('settings.automation.index', [
            'rule'   => $rule,
            'rules'  => $rule ? collect([$rule]) : collect(),
            'users'  => $users,
            'stages' => $stages,
        ]);
    }

    /**
     * ذخیره تنظیمات
     */
    public function update(Request $request)
    {
        $validStages = ['draft','send_for_approval','approved','rejected'];

        $data = $request->validate([
            // 'proforma_stage' => ['required','in:'.implode(',', $validStages)],
            'operator'              => ['required','in:=,!='],
            'value'                 => ['required','in:'.implode(',', $validStages)],
            'approver_1'            => ['nullable','exists:users,id','different:approver_2','different:emergency_approver_id'],
            'approver_2'            => ['nullable','exists:users,id','different:approver_1','different:emergency_approver_id'],
            'emergency_approver_id' => ['nullable','exists:users,id','different:approver_1','different:approver_2'],
        ]);

        if (empty($data['approver_1']) && empty($data['approver_2']) && empty($data['emergency_approver_id'])) {
            return back()->withErrors([
                'approver_1' => 'حداقل یکی از تأییدکننده‌ها یا ادمین جایگزین باید انتخاب شود.',
            ])->withInput();
        }

        DB::transaction(function () use ($data) {
            // ما فقط یک قانون داریم: یا پیدا کن یا بساز
            $rule = AutomationRule::first() ?: new AutomationRule();

            $rule->proforma_stage        = 'send_for_approval'; // اگر می‌خواهی از فرم بگیری، ولیدیشن بالا را فعال کن
            $rule->operator              = $data['operator'];
            $rule->value                 = $data['value'];
            $rule->emergency_approver_id = $data['emergency_approver_id'] ?? null;
            $rule->save();

            // ثبت approverها با priority
            AutomationRuleApprover::where('automation_rule_id', $rule->id)->delete();

            if (!empty($data['approver_1'])) {
                AutomationRuleApprover::create([
                    'automation_rule_id' => $rule->id,
                    'user_id'            => $data['approver_1'],
                    'priority'           => 1,
                ]);
            }
            if (!empty($data['approver_2'])) {
                AutomationRuleApprover::create([
                    'automation_rule_id' => $rule->id,
                    'user_id'            => $data['approver_2'],
                    'priority'           => 2,
                ]);
            }
        });

        return back()->with('success', 'تنظیمات با موفقیت ذخیره شد.');
    }

    /**
     * حذف همه قوانین (در این معماری فقط یک قانون داریم)
     */
    public function destroyAll()
    {
        DB::transaction(function () {
            DB::table('automation_rule_approvers')->delete();
            DB::table('automation_rules')->delete();
        });

        return back()->with('success', 'قانون اتوماسیون حذف شد.');
    }
}
