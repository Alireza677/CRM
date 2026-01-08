<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PhoneCall;
use App\Models\User;
use App\Services\DuplicateMobileFinder;
use App\Services\Telephony\NovatelCdrClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncNovatelCdrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?int $minutes = null,
        public ?string $destination = null,
        public ?string $queueId = null,
        public ?string $callType = null
    ) {
    }

    public function handle(NovatelCdrClient $client, DuplicateMobileFinder $mobileFinder): void
    {
        $tenant = config('services.novatel.tenant');
        $token = config('services.novatel.token');
        $baseUrl = config('services.novatel.base_url');

        if (! $tenant || ! $token || ! $baseUrl) {
            Log::warning('Novatel CDR sync skipped - missing configuration', [
                'tenant' => $tenant,
                'token' => $token ? 'set' : 'missing',
                'base_url' => $baseUrl,
            ]);

            return;
        }

        $from = $this->resolveFromTime();
        $to = $this->resolveToTime();

        $filters = [
            'destination' => $this->destination ?? config('services.novatel.destination'),
            'queueID' => $this->queueId ?? config('services.novatel.queue_id'),
            'from_date' => $from->format('Y-m-d H:i:s'),
            'to_date' => $to->format('Y-m-d H:i:s'),
            'call_type' => $this->callType ?? config('services.novatel.call_type'),
        ];

        Log::info('Novatel CDR sync started', [
            'tenant' => $tenant,
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'filters' => $filters,
        ]);

        $offset = 0;
        $page = 0;
        $total = 0;
        $visitedOffsets = [];

        while (true) {
            $page++;
            $response = $client->fetchCdrLogs($tenant, $offset, $filters);
            $logs = $response['logs'] ?? [];
            $count = is_array($logs) ? count($logs) : 0;

            Log::info('Novatel CDR sync page', [
                'tenant' => $tenant,
                'page' => $page,
                'offset' => $offset,
                'count' => $count,
            ]);

            if ($count === 0) {
                break;
            }

            $rows = [];
            foreach ($logs as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $mapped = $this->mapCdrEntry($entry, $mobileFinder);
                if ($mapped) {
                    $rows[] = $mapped;
                }
            }

            if ($rows) {
                PhoneCall::upsert(
                    $rows,
                    ['external_id'],
                    [
                        'status',
                        'customer_number',
                        'customer_id',
                        'customer_name',
                        'notes',
                        'handled_by_user_id',
                        'source_identifier',
                        'started_at',
                        'direction',
                        'payload_raw',
                        'updated_at',
                    ]
                );
                $total += count($rows);
            }

            $nextOffset = $response['next_offset'] ?? ($offset + $count);
            if ($nextOffset === $offset || in_array($nextOffset, $visitedOffsets, true)) {
                Log::warning('Novatel CDR sync halted - offset did not advance', [
                    'tenant' => $tenant,
                    'offset' => $offset,
                    'next_offset' => $nextOffset,
                ]);
                break;
            }

            $visitedOffsets[] = $offset;
            $offset = $nextOffset;
        }

        Log::info('Novatel CDR sync completed', [
            'tenant' => $tenant,
            'pages' => $page,
            'total_upserts' => $total,
        ]);
    }

    private function resolveFromTime(): Carbon
    {
        if ($this->from) {
            return Carbon::parse($this->from);
        }

        $minutes = $this->minutes ?? (int) config('services.novatel.cdr_default_minutes', 10);

        return now()->subMinutes(max(1, $minutes));
    }

    private function resolveToTime(): Carbon
    {
        if ($this->to) {
            return Carbon::parse($this->to);
        }

        return now();
    }

    private function mapCdrEntry(array $entry, DuplicateMobileFinder $mobileFinder): ?array
    {
        $externalId = $this->resolveExternalId($entry);
        if (! $externalId) {
            $externalId = $this->buildFallbackExternalId($entry);
        }

        if (! $externalId) {
            return null;
        }

        $direction = $this->normalizeDirection(
            $this->firstValue($entry, ['direction', 'call_direction', 'direction_type', 'type', 'callType', 'call_type'])
        );

        $status = $this->normalizeStatus(
            $this->firstValue($entry, ['status', 'call_status', 'state', 'call_state', 'result', 'disposition'])
        );

        $startedAt = $this->parseDateTime(
            $this->firstValue($entry, ['started_at', 'start_time', 'start', 'call_start', 'timestamp', 'start_timestamp', 'start_at'])
        );

        $customerNumber = $this->resolveCustomerNumber($entry, $direction);
        $normalizedNumber = $this->normalizePhoneNumber($customerNumber, $mobileFinder);

        $customerName = $this->resolveCustomerName($entry, $normalizedNumber, $mobileFinder);
        $handledByUserId = $this->resolveHandledByUserId($entry, $mobileFinder);

        $notes = $this->firstValue($entry, ['notes', 'note', 'description', 'comment', 'reason']);
        $sourceIdentifier = $this->resolveSourceIdentifier($entry) ?? $externalId;

        return [
            'external_id' => (string) $externalId,
            'status' => $status ?: 'unknown',
            'customer_number' => $normalizedNumber ?: ($customerNumber ?: 'unknown'),
            'customer_id' => null,
            'customer_name' => $customerName,
            'notes' => $notes ? (string) $notes : null,
            'handled_by_user_id' => $handledByUserId,
            'source_identifier' => $sourceIdentifier ? (string) $sourceIdentifier : null,
            'started_at' => $startedAt?->toDateTimeString(),
            'direction' => $direction ?: 'inbound',
            'payload_raw' => json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function resolveExternalId(array $entry): ?string
    {
        $value = $this->firstValue($entry, [
            'uniqueId',
            'unique_id',
            'uniqueID',
            'callId',
            'call_id',
            'callid',
            'linkedId',
            'linked_id',
            'cdrId',
            'cdr_id',
            'uuid',
            'id',
        ]);

        return $value !== null ? (string) $value : null;
    }

    private function buildFallbackExternalId(array $entry): ?string
    {
        $parts = [
            $this->firstValue($entry, ['from', 'src', 'caller', 'caller_id']),
            $this->firstValue($entry, ['to', 'dst', 'destination', 'dialed_number']),
            $this->firstValue($entry, ['start_time', 'started_at', 'timestamp', 'start']),
            $this->firstValue($entry, ['duration', 'billsec', 'talk_time']),
        ];

        $seed = array_filter($parts, static fn ($value) => $value !== null && $value !== '');
        if (! $seed) {
            return null;
        }

        return 'cdr_' . sha1(json_encode($seed));
    }

    private function resolveSourceIdentifier(array $entry): ?string
    {
        $value = $this->firstValue($entry, [
            'source_identifier',
            'source',
            'src',
            'caller_id',
            'agent',
            'agent_id',
            'agent_extension',
            'extension',
            'queueId',
            'queue_id',
        ]);

        return $value !== null ? (string) $value : null;
    }

    private function resolveCustomerNumber(array $entry, ?string $direction): ?string
    {
        $fromKeys = ['customer_number', 'from', 'from_number', 'caller', 'caller_number', 'caller_id', 'src', 'source', 'ani'];
        $toKeys = ['to', 'to_number', 'destination', 'dst', 'called', 'called_number', 'dialed_number'];

        $fromNumber = $this->firstValue($entry, $fromKeys);
        $toNumber = $this->firstValue($entry, $toKeys);

        $value = $direction === 'outbound' ? ($toNumber ?? $fromNumber) : ($fromNumber ?? $toNumber);

        return $value !== null ? (string) $value : null;
    }

    private function resolveCustomerName(array $entry, ?string $normalizedNumber, DuplicateMobileFinder $mobileFinder): ?string
    {
        if ($normalizedNumber) {
            $contact = $mobileFinder->findContactByMobile($normalizedNumber);
            if ($contact instanceof Contact) {
                return $contact->name ?: null;
            }

            $lead = $mobileFinder->findLeadByMobile($normalizedNumber);
            if ($lead instanceof Lead) {
                return $lead->full_name ?: $lead->company ?: null;
            }
        }

        $value = $this->firstValue($entry, [
            'customer_name',
            'caller_name',
            'name',
            'full_name',
            'contact_name',
        ]);

        return $value !== null ? (string) $value : null;
    }

    private function resolveHandledByUserId(array $entry, DuplicateMobileFinder $mobileFinder): ?int
    {
        $userId = $this->firstValue($entry, ['handled_by_user_id', 'agent_user_id', 'user_id', 'operator_id', 'agent_id']);
        if ($userId !== null && is_numeric($userId)) {
            $user = User::query()->find((int) $userId);
            if ($user) {
                return (int) $user->id;
            }
        }

        $agentMobile = $this->firstValue($entry, ['agent_mobile', 'agent_phone', 'agent_number']);
        if ($agentMobile) {
            $normalized = $this->normalizePhoneNumber((string) $agentMobile, $mobileFinder);
            if ($normalized) {
                $user = User::query()->where('mobile', $normalized)->first();
                if ($user) {
                    return (int) $user->id;
                }
            }
        }

        return null;
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
            Str::contains($normalized, ['complete', 'end', 'hangup']) => 'completed',
            default => $normalized,
        };
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }

            return Carbon::createFromTimestamp($timestamp);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
