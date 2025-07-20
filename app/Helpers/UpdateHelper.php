<?php

namespace App\Helpers;

use App\Helpers\FormOptionsHelper;
use App\Helpers\DateHelper;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Log;

class UpdateHelper
{
    public static function beautify($value, $key = null)
    {
        if (is_null($key)) return $value;

        // اگر مقدار آرایه باشد (مثلاً {"value": "1404-04-19T00:00:00.000000Z"})
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }

        $value = trim((string) $value);

        switch ($key) {
            case 'lead_status':
                return FormOptionsHelper::getLeadStatusLabel($value);

            case 'lead_source':
                return FormOptionsHelper::getLeadSourceLabel($value);

            case 'customer_type':
                return $value === '1'
                    ? 'مشتری حقیقی'
                    : ($value === '2' ? 'مشتری حقوقی' : $value);

            case 'next_follow_up':
                Log::info('next_follow_up (raw):', ['value' => $value]);

                try {
                    if (empty($value)) return null;

                    // اگر تاریخ شمسی با فرمت Z مثل 1404-04-19T00:00:00.000000Z
                    if (preg_match('/^14\d{2}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z$/', $value)) {
                        $datePart = substr($value, 0, 10); // فقط بخش yyyy-mm-dd
                        return str_replace('-', '/', $datePart); // به شمسی ساده
                    }

                    // اگر تاریخ شمسی با / یا - بدون زمان باشه
                    if (preg_match('/^14\d{2}[-\/]\d{2}[-\/]\d{2}$/', $value)) {
                        return str_replace('-', '/', $value);
                    }

                    // اگر میلادی باشه
                    $carbon = Carbon::parse($value);
                    return DateHelper::toJalali($carbon, 'Y/m/d');

                } catch (\Exception $e) {
                    Log::warning('Date parse failed for next_follow_up', [
                        'value' => $value,
                        'error' => $e->getMessage(),
                    ]);
                    return $value;
                }

            default:
                return $value;
        }
    }
}
