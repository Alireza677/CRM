<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SmsLog;

class SmsWebhookController extends Controller
{
    /**
     * Faraz Edge (IPPANEL) DLR webhook endpoint
     * Accepts flexible payloads and updates SmsLog by provider_message_id.
     */
    public function farazEdge(Request $request)
    {
        $payload = $request->all();

        // Try to extract provider message id and status with multiple fallbacks
        $providerId = $payload['message_id']
            ?? $payload['messageid']
            ?? $payload['id']
            ?? $payload['provider_message_id']
            ?? null;

        $statusRaw = $payload['status']
            ?? $payload['statustext']
            ?? $payload['delivery_status']
            ?? null;

        $errorCode = $payload['error_code'] ?? ($payload['status_code'] ?? null);
        $errorMsg  = $payload['error_message'] ?? ($payload['statustext'] ?? ($payload['message'] ?? null));

        // Some providers batch multiple items; handle common array formats
        if (!$providerId && is_array($payload)) {
            foreach (['data', 'items', 'messages', 'recipients'] as $k) {
                if (isset($payload[$k]) && is_array($payload[$k]) && count($payload[$k])) {
                    $first = $payload[$k][0];
                    $providerId = $first['message_id'] ?? $first['id'] ?? $first['messageid'] ?? null;
                    $statusRaw  = $first['status'] ?? $first['statustext'] ?? $statusRaw;
                    $errorCode  = $first['error_code'] ?? $errorCode;
                    $errorMsg   = $first['error_message'] ?? $errorMsg;
                    break;
                }
            }
        }

        if (!$providerId) {
            Log::channel('sms')->warning('[SMS][DLR] Missing provider id in webhook', [
                'payload' => $payload,
            ]);
            return response()->json(['ok' => false, 'message' => 'missing provider id'], 400);
        }

        $status = $this->normalizeStatus($statusRaw);

        $affected = SmsLog::where('provider_message_id', (string) $providerId)
            ->limit(1)
            ->update([
                'status'            => $status,
                'status_updated_at' => now(),
                'error_code'        => $status === 'failed' ? (string) $errorCode : null,
                'error_message'     => $status === 'failed' ? (string) $errorMsg : null,
            ]);

        if ($affected === 0) {
            Log::channel('sms')->warning('[SMS][DLR] No matching SmsLog for provider id', [
                'provider_message_id' => $providerId,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function normalizeStatus($raw): string
    {
        $v = strtolower((string) $raw);
        return match (true) {
            str_contains($v, 'deliver') => 'delivered',
            str_contains($v, 'fail')    => 'failed',
            str_contains($v, 'reject')  => 'rejected',
            str_contains($v, 'accept')  => 'accepted',
            str_contains($v, 'queue')   => 'queued',
            str_contains($v, 'send')    => 'sent',
            default                     => ($v !== '' ? $v : 'unknown'),
        };
    }
}

