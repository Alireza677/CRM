<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesLead;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        
        Log::info('📥 دریافت سرنخ جدید از سایت', [
            'request_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:200',
                'city' => 'nullable|string|max:100',
                'mobile' => 'nullable|string|max:20',
                'lead_source' => 'required|string|max:100',
                'assigned_to' => 'required|integer|exists:users,id',
                'building_usage' => 'nullable|string|max:255',
                'internal_temperature' => 'nullable|numeric',
                'external_temperature' => 'nullable|numeric',
                'building_length' => 'nullable|numeric',
                'building_width' => 'nullable|numeric',
                'eave_height' => 'nullable|numeric',
                'ridge_height' => 'nullable|numeric',
                'wall_material' => 'nullable|string|max:255',
                'insulation_status' => 'nullable|string|max:20',
                'spot_heating_systems' => 'nullable|integer',
                'central_200_systems' => 'nullable|integer',
                'central_300_systems' => 'nullable|integer',
            ]);

            // افزودن فیلدهای پیش‌فرض
            $validated['lead_status'] = 'new';
            $validated['lead_date'] = now()->toDateString();
            $validated['created_by'] = 1;

            $lead = SalesLead::create($validated);

            Log::info('✅ سرنخ ذخیره شد', ['lead_id' => $lead->id]);

            return response()->json([
                'status' => 'success',
                'lead_id' => $lead->id,
            ], 201);

        } catch (\Throwable $e) {
            
            Log::error('❌ خطا در ذخیره سرنخ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'ثبت سرنخ با خطا مواجه شد.',
            ], 500);
        }
    }
}
