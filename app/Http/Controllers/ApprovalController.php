<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;

class ApprovalController extends Controller
{
    /**
     * نمایش تأییدیه‌های در انتظار برای کاربر فعلی
     */
    public function pending()
    {
        $userId = auth()->id();

        $pendingApprovals = Approval::with('approvable')
            ->where('status', 'pending')
            ->where('approver_id', $userId)
            ->latest()
            ->get();

        return view('approvals.pending', compact('pendingApprovals'));
    }

    public function handleAction(Request $request, Approval $approval)
        {
            $this->authorize('update', $approval); // اختیاری: بررسی مالکیت

            $request->validate([
                'decision' => 'required|in:approved,rejected',
            ]);

            $approval->status = $request->decision;
            $approval->save();

            return redirect()->route('approvals.pending')->with('success', 'وضعیت تأییدیه به‌روزرسانی شد.');
        }

}
