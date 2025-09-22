<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Proforma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaApprovalController extends Controller
{
    /**
     * POST /sales/proformas/{proforma}/approvals/{step}/{decision}
     * decision: approve|reject
     * step: عددی (1 یا 2)
     */
    public function decide(Request $request, Proforma $proforma, int $step, string $decision)
    {
        // ولیدیشن پارامترهای مسیر
        $decision = strtolower($decision);
        if (!in_array($decision, ['approve', 'reject'], true)) {
            abort(400, 'تصمیم نامعتبر است.');
        }
        if ($step < 1 || $step > 2) {
            abort(400, 'مرحله نامعتبر است.');
        }

        // اگر نیاز داری علت رد را ذخیره کنی:
        $reason = trim((string) $request->input('reason', ''));

        // (اختیاری) احراز هویت/مجوز: چک کن کاربر فعلی همان approver باشد
        // if ($step === 1 && $proforma->first_approver_id !== auth()->id()) abort(403);
        // if ($step === 2 && $proforma->second_approver_id !== auth()->id()) abort(403);

        DB::transaction(function () use ($proforma, $decision, $step, $reason) {
            // منطق ساده و عمومی:
            // وضعیت‌های پیشنهادی: draft | awaiting_step2 | approved | rejected | sent_for_approval
            // هر پروژه ممکنه اسم‌های خودش را داشته باشد—اینجا حداقل را پیاده می‌کنیم.

            if ($decision === 'reject') {
                $proforma->approval_stage = 'rejected';
                if ($reason !== '') {
                    // اگر ستون دارید:
                    if ($proforma->isFillable('rejection_reason')) {
                        $proforma->rejection_reason = $reason;
                    }
                }
                $proforma->save();

                // (اختیاری) اینجا می‌توانی نوتیفیکیشن ارسال کنی
                // $proforma->notifyRejected();

                return;
            }

            // approve:
            if ($step === 1) {
                // اگر تأیید دوم داری، بفرست مرحله بعد؛ وگرنه نهایی کن
                $hasSecond = false;
                if ($proforma->getAttribute('second_approver_id')) {
                    $hasSecond = (bool) $proforma->second_approver_id;
                }

                if ($hasSecond) {
                    $proforma->approval_stage = 'awaiting_step2';
                } else {
                    $proforma->approval_stage = 'approved';
                    if ($proforma->isFillable('approved_at')) {
                        $proforma->approved_at = now();
                    }
                }
                $proforma->save();
            } else { // step === 2
                $proforma->approval_stage = 'approved';
                if ($proforma->isFillable('approved_at')) {
                    $proforma->approved_at = now();
                }
                $proforma->save();
            }

           
        });

        // برگشت به صفحه قبل با پیام موفقیت
        return back()->with('status', $decision === 'approve'
            ? 'پیش‌فاکتور با موفقیت تأیید شد.'
            : 'پیش‌فاکتور رد شد.');
    }
}
