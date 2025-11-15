<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AfterSalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Log::info('Received after-sales service request', [
            'raw_body' => $request->getContent(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $validated = $request->validate([
                'customer_name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:1000'],
                'coordinator_name' => ['required', 'string', 'max:255'],
                'coordinator_mobile' => ['required', 'string', 'max:32'],
                'issue_description' => ['required', 'string', 'max:2000'],
            ]);

            $serviceRequest = AfterSalesService::create($validated + [
                'created_by_id' => 1,
            ]);

            Log::info('After-sales service request stored', [
                'service_request_id' => $serviceRequest->id,
            ]);

            return response()->json([
                'status' => 'success',
                'service_request_id' => $serviceRequest->id,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Failed to store after-sales service request', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to create service request at this time.',
            ], 500);
        }
    }
}
