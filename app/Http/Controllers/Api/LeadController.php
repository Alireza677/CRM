<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SalesLeadController;
use Illuminate\Http\Request;
use App\Models\SalesLead;
use App\Models\Contact;
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

            /** @var SalesLeadController $helper */
            $helper = app(SalesLeadController::class);
            $normalizedMobile = $helper->normalizeMobile($validated['mobile'] ?? null);
            if ($normalizedMobile) {
                $validated['mobile'] = $normalizedMobile;
                $existingLead = $helper->findLeadByNormalizedMobile($normalizedMobile);
                if ($existingLead) {
                    $existingStatus = SalesLead::normalizeStatus($existingLead->lead_status ?? $existingLead->status);

                    if ($existingStatus === SalesLead::STATUS_DISCARDED) {
                        $reactivatedLead = $helper->reactivateDiscardedLead($existingLead, $validated, false);

                        return response()->json([
                            'status'  => 'reengaged',
                            'lead_id' => $reactivatedLead->id,
                            'message' => 'این سرنخ قبلاً حذف شده بود و حالا دوباره فعال شد.',
                        ]);
                    }

                    $updatePayload = $validated;
                    unset(
                        $updatePayload['lead_status'],
                        $updatePayload['created_by'],
                        $updatePayload['lead_date'],
                        $updatePayload['assigned_to']
                    );

                    if (!empty($existingLead->lead_source)) {
                        $updatePayload['lead_source'] = $existingLead->lead_source;
                    }

                    $existingLead->fill($updatePayload);
                    $existingLead->is_reengaged = true;
                    $existingLead->reengaged_at = now();
                    $existingLead->save();

                    return response()->json([
                        'status'  => 'duplicate_reengaged',
                        'lead_id' => $existingLead->id,
                        'message' => 'این سرنخ قبلاً وجود داشت و به‌روزرسانی شد.',
                    ]);
                }
            }

            $lead = SalesLead::create($validated);

            if (empty($lead->contact_id)) {
                $contact = $this->createContactFromLead($lead);
                if ($contact) {
                    $lead->forceFill(['contact_id' => $contact->id])->saveQuietly();
                    $lead->contacts()->syncWithoutDetaching([$contact->id]);
                }
            }

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

    private function createContactFromLead(SalesLead $lead): ?Contact
    {
        $hasAnyValue = !empty($lead->full_name) || !empty($lead->mobile);
        if (!$hasAnyValue) {
            return null;
        }

        [$firstName, $lastName] = $this->splitLeadName($lead->full_name);

        return Contact::create([
            'owner_user_id' => $lead->owner_user_id ?? 1,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'mobile'        => $lead->mobile,
            'state'         => $lead->state,
            'city'          => $lead->city,
            'assigned_to'   => $lead->assigned_to,
        ]);
    }

    private function splitLeadName(?string $fullName): array
    {
        if (!$fullName) {
            return [null, null];
        }

        $parts = preg_split('/\s+/', trim($fullName));
        $lastName = array_pop($parts);
        $firstName = trim(implode(' ', $parts));

        if ($firstName === '') {
            $firstName = $lastName;
            $lastName = null;
        }

        return [$firstName ?: null, $lastName ?: null];
    }
}

