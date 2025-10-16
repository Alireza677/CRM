<?php
namespace App\Services\Sms;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FarazEdgeService
{
    protected string $baseUrl;
    protected string $token;        // می‌تونه API Key یا Token باشد (خام)
    protected string $defaultFrom;

    public function __construct()
    {
        $this->baseUrl     = rtrim((string) config('services.faraz_edge.base_url', 'https://edge.ippanel.com/v1'), '/');
        $this->token       = trim((string) config('services.faraz_edge.token', ''));   // بدون Bearer
        $this->defaultFrom = trim((string) config('services.faraz_edge.from', ''));

        if ($this->token === '') {
            throw new \InvalidArgumentException('FARAZ_EDGE token/API key is not set. Add it to .env and clear config cache.');
        }
    }

    public function sendWebservice(string|array $recipients, string $message, ?string $from = null, ?string $sendTimeUtc = null): array
    {
        $traceId = bin2hex(random_bytes(6));
        $t0 = microtime(true);

        $to = is_array($recipients) ? array_values($recipients) : [$recipients];

        $payload = [
            'sending_type' => 'webservice',
            'from_number'  => $from ?: $this->defaultFrom,
            'message'      => $message,
            'params'       => ['recipients' => $to],
        ];
        if ($sendTimeUtc) {
            $payload['send_time'] = $sendTimeUtc; // UTC 'YYYY-MM-DD HH:MM:SS'
        }

        // لاگ پیش از ارسال
        Log::channel('sms')->info("[SMS][REQ][$traceId] FarazEdge preflight", [
            'endpoint'       => $this->baseUrl . '/api/send',
            'from_number'    => $payload['from_number'],
            'recipients_cnt' => count($to),
            'payload_clip'   => $this->clip($payload),
            'token_masked'   => $this->mask($this->token),
        ]);

        $http = Http::withHeaders([
            'Authorization' => $this->token,           // ❗️بدون Bearer
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->withOptions([
            'http_errors' => false,
            'verify'      => $this->detectCaPath() ?: true,
        ]);

        $res     = $http->post($this->baseUrl . '/api/send', $payload);
        $status  = $res->status();
        $reason  = $res->reason();
        $bodyStr = (string) $res->getBody();

        Log::channel('sms')->info("[SMS][RES][$traceId] FarazEdge response", [
            'status'      => $status,
            'reason'      => $reason,
            'headers'     => $this->sanitizeHeaders($res->headers()),
            'body_clip'   => $this->clip($bodyStr),
            'duration_ms' => (int) round((microtime(true) - $t0) * 1000),
        ]);

        if ($res->failed()) {
            Log::channel('sms')->warning("[SMS][ERR][$traceId] HTTP failed", [
                'status'    => $status,
                'reason'    => $reason,
                'body_clip' => $this->clip($bodyStr),
            ]);
            throw new RequestException($res);
        }

        $json = $res->json();
        if (!data_get($json, 'meta.status')) {
            Log::channel('sms')->warning("[SMS][ERR][$traceId] meta.status=false", [
                'meta_clip' => $this->clip(data_get($json, 'meta')),
                'body_clip' => $this->clip($json),
            ]);
            throw new \RuntimeException('Faraz Edge error: ' . (data_get($json, 'meta.message') ?? 'unknown'));
        }

        return [
            'ok'       => true,
            'bulk_ids' => (array) data_get($json, 'data.message_outbox_ids', []),
            'meta'     => data_get($json, 'meta'),
            'request'  => $payload,
            'raw'      => $json,
        ];
    }

    private function detectCaPath(): ?string
    {
        foreach ([env('HTTP_CA_BUNDLE'), env('CURL_CA_BUNDLE'), env('SSL_CERT_FILE')] as $p) {
            if (is_string($p) && $p !== '' && is_file($p)) return $p;
        }
        $pem = storage_path('app/certs/cacert.pem');
        return is_file($pem) ? $pem : null;
    }

    private function sanitizeHeaders(array $h): array
    {
        unset($h['set-cookie'],$h['Set-Cookie'],$h['cookie'],$h['Cookie']);
        return $h;
    }

    private function mask(string $s): string
    {
        $len = mb_strlen($s);
        if ($len <= 8) return str_repeat('*', $len);
        return mb_substr($s,0,4) . str_repeat('*', $len-8) . mb_substr($s,-4);
    }

    private function clip(mixed $v, int $n=2000): string
    {
        $s = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        if ($s === false) return '[unserializable]';
        return mb_strlen($s) > $n ? (mb_substr($s,0,$n).'...') : $s;
    }
}
