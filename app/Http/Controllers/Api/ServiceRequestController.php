<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AfterSalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServiceRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Log::info('POST /api/service-requests hit', [
            'method' => $request->getMethod(),
            'ip' => $request->ip(),
            'client' => [
                'user_agent' => $request->userAgent(),
            ],
            'request_input' => $request->all(),
            'headers' => $request->headers->all(),
            'raw_body' => $request->getContent(),
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:1000'],
                'coordinator_name' => ['required', 'string', 'max:255'],
                'coordinator_mobile' => ['required', 'string', 'max:32'],
                'issue_description' => ['required', 'string', 'max:2000'],
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();

                Log::warning('After-sales service request validation failed', [
                    'errors' => $errors,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed.',
                    'errors' => $errors,
                ], 422);
            }

            $validated = $validator->validated();

            Log::info('Validation passed for after-sales service request', [
                'validated_data' => $validated,
            ]);

            $payload = $validated + [
                'created_by_id' => 1,
            ];

            Log::info('Persisting after-sales service request', [
                'payload' => $payload,
            ]);

            $serviceRequest = AfterSalesService::create($payload);

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
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to create service request at this time.',
            ], 500);
        }
    }
}
