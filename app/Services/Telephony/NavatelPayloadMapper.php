<?php

namespace App\Services\Telephony;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NavatelPayloadMapper
{
    public function map(array $payload, array $query = []): array
    {
        $data = array_merge($query, $payload);

        $direction = $this->normalizeDirection(
            $this->firstValue($data, [
                'direction',
                'call_direction',
                'direction_type',
                'type',
            ])
        );

        $status = $this->normalizeStatus(
            $this->firstValue($data, [
                'status',
                'call_status',
                'state',
                'call_state',
            ])
        );

        $customerNumber = $this->resolveCustomerNumber($data, $direction);

        $sourceIdentifier = $this->firstValue($data, [
            'source_identifier',
            'call_id',
            'callid',
            'call_uuid',
            'uuid',
            'unique_id',
            'session_id',
            'sid',
        ]);

        $startedAt = $this->parseDateTime(
            $this->firstValue($data, [
                'started_at',
                'start_time',
                'start',
                'call_start',
                'timestamp',
                'start_timestamp',
                'start_at',
                'call_started_at',
            ])
        );

        return [
            'status' => $status ?: 'unknown',
            'direction' => $direction ?: 'inbound',
            'customer_number' => $customerNumber ?: 'unknown',
            'customer_id' => $this->firstValue($data, ['customer_id']),
            'customer_name' => $this->firstValue($data, [
                'customer_name',
                'caller_name',
                'name',
                'full_name',
                'contact_name',
            ]),
            'notes' => $this->firstValue($data, [
                'notes',
                'note',
                'description',
                'comment',
                'reason',
            ]),
            'handled_by_user_id' => $this->firstValue($data, [
                'handled_by_user_id',
                'agent_id',
                'user_id',
                'operator_id',
            ]),
            'source_identifier' => $sourceIdentifier ? (string) $sourceIdentifier : null,
            'started_at' => $startedAt,
        ];
    }

    private function resolveCustomerNumber(array $data, ?string $direction): ?string
    {
        $fromKeys = [
            'customer_number',
            'from',
            'from_number',
            'caller',
            'caller_number',
            'caller_id',
            'src',
            'source',
            'ani',
            'calling_number',
        ];

        $toKeys = [
            'to',
            'to_number',
            'destination',
            'dst',
            'called',
            'called_number',
            'dialed_number',
        ];

        $fromNumber = $this->firstValue($data, $fromKeys);
        $toNumber = $this->firstValue($data, $toKeys);

        $value = $direction === 'outbound' ? ($toNumber ?? $fromNumber) : ($fromNumber ?? $toNumber);

        return $value !== null ? (string) $value : null;
    }

    private function firstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (Arr::has($data, $key)) {
                $value = Arr::get($data, $key);
                if ($value !== null && $value !== '') {
                    return $value;
                }
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

        if (Str::contains($normalized, ['out', 'outbound', 'dialout'])) {
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

        return strtolower(trim((string) $value));
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
