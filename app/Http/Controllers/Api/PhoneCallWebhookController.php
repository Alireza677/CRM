<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use App\Models\TelephonyWebhookEvent;
use App\Services\DuplicateMobileFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PhoneCallWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $traceId = $request->header('X-Trace-Id') ?: (string) Str::uuid();
        $payload = $this->extractPayload($request);
        $query = $request->query();

        $source = $request->header('X-Webhook-Source')
            ?? ($payload['source'] ?? null)
            ?? 'novatel';

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

        $apiKey = config('services.novatel.api_key');
        if ($apiKey) {
            $providedKey = $request->header('ApiKey') ?? $request->header('X-Api-Key');
            if (! is_string($providedKey) || ! hash_equals($apiKey, $providedKey)) {
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
            Log::warning('Novatel webhook api key is not configured', [
                'trace_id' => $traceId,
                'environment' => app()->environment(),
            ]);
        }

        try {
            $mobileFinder = new DuplicateMobileFinder();
            $mapped = $this->mapPayload($payload, $query, $mobileFinder);

            if (! $mapped['external_id']) {
                throw new \RuntimeException('Missing call identifier');
            }

            $phoneCall = PhoneCall::firstOrNew([
                'external_id' => $mapped['external_id'],
            ]);

            $incomingStatus = $mapped['status'] ?? null;
            $finalStatus = $this->mergeStatus($phoneCall->status ?? null, $incomingStatus);

            $phoneCall->status = $finalStatus ?: 'unknown';
            $phoneCall->customer_number = $mapped['customer_number'] ?? ($phoneCall->customer_number ?: 'unknown');
            $phoneCall->customer_name = $mapped['customer_name'] ?? $phoneCall->customer_name;
            $phoneCall->notes = $this->mergeNotes($phoneCall->notes, $mapped['notes'] ?? null, $mapped['recording_url'] ?? null);
            $phoneCall->handled_by_user_id = $mapped['handled_by_user_id'] ?? $phoneCall->handled_by_user_id;
            $phoneCall->source_identifier = $mapped['source_identifier'] ?? $phoneCall->source_identifier ?? $mapped['external_id'];
            $phoneCall->started_at = $mapped['started_at'] ?? $phoneCall->started_at;
            $phoneCall->direction = $mapped['direction'] ?? $phoneCall->direction ?? 'inbound';
            $phoneCall->payload_raw = $mapped['payload_raw'] ?? $payload;

            $phoneCall->save();

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

    private function mapPayload(array $payload, array $query, DuplicateMobileFinder $mobileFinder): array
    {
        $data = array_merge($query, $payload);

        $externalId = $this->resolveExternalId($data);
        if (! $externalId && ! empty($data)) {
            $externalId = 'call_' . sha1(json_encode($data));
        }

        $direction = $this->normalizeDirection($this->firstValue($data, [
            'direction',
            'call_direction',
            'callDirection',
            'CallDirection',
            'type',
            'call_type',
            'callType',
            'CallType',
        ]));

        $status = $this->normalizeStatus($this->firstValue($data, [
            'callStatus',
            'call_status',
            'CallStatus',
            'status',
            'Status',
            'state',
            'call_state',
        ]));

        $startedAt = $this->parseDateTime($this->firstValue($data, [
            'CallDateTime',
            'callDateTime',
            'call_datetime',
            'call_date_time',
            'start_time',
            'start',
            'call_start',
            'timestamp',
            'start_timestamp',
            'start_at',
        ]));

        $customerNumber = $this->resolveCustomerNumber($data, $direction);
        $normalizedNumber = $this->normalizePhoneNumber($customerNumber, $mobileFinder);

        $customerName = $this->resolveCustomerName($data, $normalizedNumber, $mobileFinder);

        $notes = $this->firstValue($data, [
            'notes',
            'note',
            'description',
            'comment',
            'reason',
        ]);

        $recordingUrl = $this->firstValue($data, [
            'fileUrl',
            'file_url',
            'rec_file',
            'path_rec',
            'recording_url',
        ]);

        $sourceIdentifier = $this->firstValue($data, [
            'source_identifier',
            'callID',
            'callId',
            'call_id',
            'callid',
            'uniqueId',
            'unique_id',
            'linkedId',
            'linked_id',
        ]) ?? $externalId;

        return [
            'external_id' => $externalId ? (string) $externalId : null,
            'status' => $status ?: 'unknown',
            'customer_number' => $normalizedNumber ?: ($customerNumber ? (string) $customerNumber : null),
            'customer_name' => $customerName,
            'notes' => $notes ? (string) $notes : null,
            'recording_url' => $recordingUrl ? (string) $recordingUrl : null,
            'handled_by_user_id' => $this->resolveHandledByUserId($data),
            'source_identifier' => $sourceIdentifier ? (string) $sourceIdentifier : null,
            'started_at' => $startedAt,
            'direction' => $direction ?: 'inbound',
            'payload_raw' => $payload,
        ];
    }

    private function resolveExternalId(array $data): ?string
    {
        $value = $this->firstValue($data, [
            'uniqueId',
            'unique_id',
            'uniqueID',
            'linkedId',
            'linked_id',
            'callID',
            'callId',
            'call_id',
            'callid',
            'uuid',
            'id',
        ]);

        return $value !== null ? (string) $value : null;
    }

    private function resolveCustomerNumber(array $data, ?string $direction): ?string
    {
        $fromKeys = [
            'customer_number',
            'callerId',
            'caller_id',
            'CallerID',
            'caller',
            'from',
            'from_number',
            'caller_number',
            'src',
            'source',
            'ani',
        ];

        $toKeys = [
            'calledId',
            'called_id',
            'CalledID',
            'to',
            'to_number',
            'destination',
            'dst',
            'called',
            'called_number',
            'dialed_number',
        ];

        $phoneNumber = $this->firstValue($data, ['PhoneNumber', 'phoneNumber']);
        $fromNumber = $this->firstValue($data, $fromKeys);
        $toNumber = $this->firstValue($data, $toKeys);

        $value = $direction === 'outbound'
            ? ($toNumber ?? $phoneNumber ?? $fromNumber)
            : ($fromNumber ?? $phoneNumber ?? $toNumber);

        return $value !== null ? (string) $value : null;
    }

    private function resolveCustomerName(array $data, ?string $normalizedNumber, DuplicateMobileFinder $mobileFinder): ?string
    {
        if ($normalizedNumber) {
            $contact = $mobileFinder->findContactByMobile($normalizedNumber);
            if ($contact) {
                return $contact->name ?: null;
            }

            $lead = $mobileFinder->findLeadByMobile($normalizedNumber);
            if ($lead) {
                return $lead->full_name ?: $lead->company ?: null;
            }
        }

        $value = $this->firstValue($data, [
            'customer_name',
            'caller_name',
            'name',
            'full_name',
            'contact_name',
        ]);

        return $value !== null ? (string) $value : null;
    }

    private function resolveHandledByUserId(array $data): ?int
    {
        $value = $this->firstValue($data, [
            'handled_by_user_id',
            'agent_user_id',
            'user_id',
            'operator_id',
            'agent_id',
        ]);

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizePhoneNumber(?string $value, DuplicateMobileFinder $mobileFinder): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = $mobileFinder->normalizeMobile($value);
        if ($normalized) {
            return $normalized;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function mergeNotes(?string $current, ?string $incoming, ?string $recordingUrl): ?string
    {
        $notes = $current;
        if ($incoming) {
            if ($notes && ! Str::contains($notes, $incoming)) {
                $notes .= "\n" . $incoming;
            } else {
                $notes = $incoming;
            }
        }

        if (! $notes && $recordingUrl) {
            $notes = 'Recording: ' . $recordingUrl;
        } elseif ($notes && $recordingUrl && ! Str::contains($notes, $recordingUrl)) {
            $notes .= "\nRecording: " . $recordingUrl;
        }

        return $notes;
    }

    private function mergeStatus(?string $current, ?string $incoming): ?string
    {
        if (! $incoming) {
            return $current;
        }

        if (! $current) {
            return $incoming;
        }

        $terminal = ['completed', 'no-answer', 'failed', 'busy', 'canceled'];
        $currentTerminal = in_array($current, $terminal, true);
        $incomingTerminal = in_array($incoming, $terminal, true);

        if ($currentTerminal && ! $incomingTerminal) {
            return $current;
        }

        if ($incomingTerminal && ! $currentTerminal) {
            if ($incoming === 'no-answer' && $current === 'answered') {
                return $current;
            }

            return $incoming;
        }

        if ($incoming === 'completed' && $current !== 'completed') {
            return $incoming;
        }

        $rank = [
            'unknown' => 0,
            'queued' => 10,
            'ringing' => 20,
            'in-progress' => 30,
            'answered' => 40,
            'completed' => 50,
            'no-answer' => 50,
            'failed' => 50,
            'busy' => 50,
            'canceled' => 50,
        ];

        $currentRank = $rank[$current] ?? 0;
        $incomingRank = $rank[$incoming] ?? 0;

        return $incomingRank >= $currentRank ? $incoming : $current;
    }

    private function firstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeDirection(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        if (Str::contains($normalized, ['out', 'outbound', 'outgoing', 'dialout'])) {
            return 'outbound';
        }

        if (Str::contains($normalized, ['in', 'inbound', 'incoming'])) {
            return 'inbound';
        }

        return null;
    }

    private function normalizeStatus(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        return match (true) {
            Str::contains($normalized, ['answer']) => 'answered',
            Str::contains($normalized, ['miss', 'no answer', 'no-answer']) => 'no-answer',
            Str::contains($normalized, ['busy']) => 'busy',
            Str::contains($normalized, ['fail', 'error']) => 'failed',
            Str::contains($normalized, ['cancel']) => 'canceled',
            Str::contains($normalized, ['ring']) => 'ringing',
            Str::contains($normalized, ['queue']) => 'queued',
            Str::contains($normalized, ['progress']) => 'in-progress',
            Str::contains($normalized, ['hangup', 'complete', 'end']) => 'completed',
            default => $normalized,
        };
    }

    private function parseDateTime(mixed $value): ?\Illuminate\Support\Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }

            return \Illuminate\Support\Carbon::createFromTimestamp($timestamp);
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
