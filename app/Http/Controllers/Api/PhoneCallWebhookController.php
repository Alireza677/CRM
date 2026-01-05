<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use App\Models\TelephonyWebhookEvent;
use App\Services\Telephony\NavatelPayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PhoneCallWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $traceId = (string) Str::uuid();
        $payload = $this->extractPayload($request);
        $query = $request->query();

        $source = $request->header('X-Webhook-Source')
            ?? ($payload['source'] ?? null)
            ?? 'navatel';

        $event = TelephonyWebhookEvent::create([
            'trace_id' => $traceId,
            'source' => (string) $source,
            'received_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'payload' => $payload,
            'query' => $query,
            'content_type' => $request->header('Content-Type'),
            'processing_status' => 'pending',
        ]);

        $token = config('services.navatel.webhook_token');
        if ($token) {
            $providedToken = $request->header('X-Navatel-Token');
            if (! is_string($providedToken) || ! hash_equals($token, $providedToken)) {
                $event->update([
                    'processing_status' => 'failed',
                    'error_message' => 'unauthorized',
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'trace_id' => $traceId,
                    'message' => 'unauthorized',
                ], 401);
            }
        } else {
            Log::warning('Navatel webhook token is not configured', [
                'trace_id' => $traceId,
                'environment' => app()->environment(),
            ]);
        }

        try {
            $mapper = new NavatelPayloadMapper();
            $mapped = $mapper->map($payload, $query);

            $phoneCall = PhoneCall::create([
                'status' => $mapped['status'] ?? 'unknown',
                'customer_number' => $mapped['customer_number'] ?? 'unknown',
                'customer_id' => $mapped['customer_id'] ?? null,
                'customer_name' => $mapped['customer_name'] ?? null,
                'notes' => $mapped['notes'] ?? null,
                'handled_by_user_id' => $mapped['handled_by_user_id'] ?? null,
                'source_identifier' => $mapped['source_identifier'] ?? null,
                'started_at' => $mapped['started_at'] ?? now(),
                'direction' => $mapped['direction'] ?? 'inbound',
                'payload_raw' => $payload,
            ]);

            $event->update([
                'phone_call_id' => $phoneCall->id,
                'processing_status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $event->update([
                'processing_status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);
        }

        return response()->json([
            'trace_id' => $traceId,
            'message' => 'received',
        ], 200);
    }

    private function extractPayload(Request $request): array
    {
        try {
            if ($request->isJson()) {
                return $request->json()->all();
            }
        } catch (\Throwable $e) {
            // Fallback to form/query parsing below.
        }

        $files = $request->allFiles();
        $fileKeys = is_array($files) ? array_keys($files) : [];

        return $request->except($fileKeys);
    }
}
