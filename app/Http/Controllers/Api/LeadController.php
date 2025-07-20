<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        // لاگ ورودی کامل
        Log::info('📥 دریافت درخواست جدید از گرویتی فرم', [
            'request_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            // اعتبارسنجی
            $validated = $request->validate([
                'prefix' => 'nullable|string|max:20',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'company' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'mobile' => 'nullable|string|max:20',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'nationality' => 'nullable|string|max:100',
                'address' => 'nullable|string',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'lead_source' => 'required|string',
                'lead_status' => 'required|string',
                'lead_date' => 'required|date',
                'next_follow_up_date' => 'nullable|date',
                'assigned_to' => 'required|integer|exists:users,id',
            ]);

            // ذخیره در دیتابیس
            $lead = Lead::create($validated);

            // موفقیت
            Log::info('✅ سرنخ با موفقیت ثبت شد', [
                'lead_id' => $lead->id
            ]);

            return response()->json([
                'status' => 'success',
                'lead_id' => $lead->id,
            ], 201);

        } catch (\Throwable $e) {
            // در صورت خطا
            Log::error('❌ خطا در ثبت سرنخ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در پردازش اطلاعات. لطفاً بعداً تلاش کنید.',
            ], 500);
        }
    }
}
