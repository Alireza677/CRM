<?php
namespace App\Services\Sms;

use App\Models\SmsLog;

class Sms
{
    public function __construct(protected FarazSmsService $driver) {}

    public function send(string|array $to, string $message): array
    {
        $result = $this->driver->send($to, $message);

        // اگر یک شماره بود، لاگ کن
        if (is_string($to)) {
            SmsLog::create([
                'to'                 => $to,
                'type'               => 'text',
                'message'            => $message,
                'status_code'        => $result['status'] ?? null,
                'status_text'        => ($result['ok'] ?? false) ? 'OK' : 'ERROR',
                'provider_message_id'=> $result['data']['data']['bulk_id'] ?? ($result['data']['data']['message_id'] ?? null),
                'provider_response'  => $result['data'] ?? null,
            ]);
        }

        return $result;
    }

    public function sendPattern(string $to, string $patternCode, array $values): array
    {
        $result = $this->driver->sendPattern($to, $patternCode, $values);

        SmsLog::create([
            'to'                => $to,
            'type'              => 'pattern',
            'pattern_code'      => $patternCode,
            'values'            => $values,
            'status_code'       => $result['status'] ?? null,
            'status_text'       => ($result['ok'] ?? false) ? 'OK' : 'ERROR',
            'provider_message_id'=> $result['data']['data']['message_id'] ?? null,
            'provider_response' => $result['data'] ?? null,
        ]);

        return $result;
    }
}
