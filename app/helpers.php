<?php
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

if (!function_exists('jdate')) {
    function jdate($datetime, $format = 'Y/m/d')
    {
        if (empty($datetime)) {
            return '-';
        }

        try {
            // اگر عدد تک‌رقمی یا سال اشتباه باشه، حذفش کن
            if (is_numeric($datetime) && strlen($datetime) < 6) {
                return '-';
            }

            $carbonDate = $datetime instanceof Carbon
                ? $datetime
                : Carbon::parse($datetime);

            // بررسی سال معتبر قبل از تبدیل
            $year = $carbonDate->year;
            if ($year < 1000 || $year > 3000) {
                return '-';
            }

            $jalali = Jalalian::fromCarbon($carbonDate);

            return $format === 'relative'
                ? $jalali->ago()
                : $jalali->format($format);

        } catch (\Exception $e) {
            return '-';
        }
    }
}
