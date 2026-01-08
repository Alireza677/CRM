<?php

namespace App\Services\Telephony;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NovatelCdrClient
{
    public function fetchCdrLogs(string $tenant, int $offset, array $filters): array
    {
        $baseUrl = rtrim((string) config('services.novatel.base_url'), '/');
        $token = (string) config('services.novatel.token');
        $endpoint = $this->resolveEndpoint($tenant, $offset);

        $payload = $this->filterPayload($filters);

        $response = Http::baseUrl($baseUrl)
            ->withToken($token)
            ->timeout(30)
            ->acceptJson()
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            Log::warning('Novatel CDR request failed', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
                'tenant' => $tenant,
                'offset' => $offset,
                'payload' => $payload,
                'response' => $response->body(),
            ]);

            return [
                'logs' => [],
                'next_offset' => null,
                'raw' => $response->json(),
            ];
        }

        $body = $response->json();
        $logs = $this->extractLogs($body);
        $nextOffset = data_get($body, 'nextOffset')
            ?? data_get($body, 'next_offset')
            ?? data_get($body, 'offset')
            ?? data_get($body, 'data.nextOffset');

        return [
            'logs' => $logs,
            'next_offset' => is_numeric($nextOffset) ? (int) $nextOffset : null,
            'raw' => $body,
        ];
    }

    private function resolveEndpoint(string $tenant, int $offset): string
    {
        $resource = config('services.novatel.use_full_logs') ? 'cdrFullLogs' : 'cdrLogs';

        return "/cdr/api/v1/cdr/{$resource}/{$tenant}/{$offset}";
    }

    private function filterPayload(array $payload): array
    {
        return array_filter(
            $payload,
            static fn ($value) => !($value === null || $value === '')
        );
    }

    private function extractLogs(mixed $body): array
    {
        if (! is_array($body)) {
            return [];
        }

        if ($this->isList($body)) {
            return $body;
        }

        $keys = [
            'cdrLogs',
            'cdr_logs',
            'cdrs',
            'logs',
            'data',
            'result',
            'items',
            'records',
        ];

        foreach ($keys as $key) {
            $value = data_get($body, $key);
            if (is_array($value)) {
                if ($this->isList($value)) {
                    return $value;
                }

                $nested = $this->findFirstList($value);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return [];
    }

    private function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    private function findFirstList(array $payload): ?array
    {
        foreach ($payload as $value) {
            if (! is_array($value)) {
                continue;
            }

            if ($this->isList($value)) {
                return $value;
            }
        }

        return null;
    }
}
