<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneCallWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:100'],
            'customer_number' => ['required', 'string', 'max:50'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'handled_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'source_identifier' => ['nullable', 'string', 'max:191'],
            'started_at' => ['nullable', 'date'],
            'direction' => ['nullable', 'in:inbound,outbound'],
            'payload_raw' => ['nullable', 'array'],
        ]);

        $payloadRaw = $validated['payload_raw'] ?? $request->all();

        $phoneCall = PhoneCall::create([
            'status' => $validated['status'],
            'customer_number' => $validated['customer_number'],
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'handled_by_user_id' => $validated['handled_by_user_id'] ?? null,
            'source_identifier' => $validated['source_identifier'] ?? null,
            'started_at' => $validated['started_at'] ?? now(),
            'direction' => $validated['direction'] ?? 'inbound',
            'payload_raw' => $payloadRaw,
        ]);

        return response()->json([
            'message' => 'Phone call recorded',
            'data' => $phoneCall,
        ], 201);
    }
}
