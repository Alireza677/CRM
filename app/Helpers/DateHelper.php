<?php

namespace App\Helpers;

use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
use Exception;

class DateHelper
{
    /** تایم‌زون مبنا برای ایران */
    private const TZ_TEHRAN = 'Asia/Tehran';

    /**
     * تبدیل تاریخ شمسی به میلادی (خروجی: Y-m-d)
     * ورودی مثل: 1403/06/19 یا ۱۴۰۳-۰۶-۱۹
     */
    public static function toGregorian(string $jalaliDate): ?string
    {
        try {
            // اعداد فارسی و جداکننده‌ها را یکدست کن
            $jalaliDate = self::convertPersianToEnglishNumbers($jalaliDate);
            $jalaliDate = str_replace('-', '/', $jalaliDate);

            if (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $jalaliDate)) {
                \Log::warning("❗ Invalid Jalali date format after cleanup: " . $jalaliDate);
                return null;
            }

            // تبدیل به Carbon با مبنای تهران و تثبیت ساعت 12 ظهر
            $c = Jalalian::fromFormat('Y/m/d', $jalaliDate)
                ->toCarbon()                              // خروجی Carbon
                ->setTimezone(self::TZ_TEHRAN)            // مبنا: تهران
                ->setTime(12, 0, 0);                      // جلوگیری از پرش روز

            // برای ذخیره در DB فقط تاریخ گرگوریان (بدون زمان)
            return $c->format('Y-m-d');
        } catch (Exception $e) {
            \Log::error("toGregorian() error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     * - $date می‌تواند string یا Carbon باشد.
     * - همیشه به «تهرانِ ۱۲ ظهر» نرمال می‌کنیم تا پرش روز نداشته باشیم.
     */
    public static function toJalali($date, $format = 'Y/m/d', ?bool $preserveTime = null): ?string
    {
        try {
            if (empty($date)) {
                return null;
            }

            if (!$date instanceof Carbon) {
                // اگر از DB/UTC می‌آید، اول با UTC پارس کن بعد به تهران ببر
                $date = Carbon::parse($date, 'UTC');
            }

            // اگر فرمت شامل ساعت/دقیقه/ثانیه باشد، زمان واقعی را نگه دار
            // در غیر این صورت برای اجتناب از پرش روز، زمان را 12:00 قرار می‌دهیم
            $containsTimeTokens = (bool) preg_match('/[HhGgis]/', (string) $format);
            $shouldPreserveTime = $preserveTime ?? $containsTimeTokens;

            $date = $date->copy()->setTimezone(self::TZ_TEHRAN);
            if (!$shouldPreserveTime) {
                $date = $date->setTime(12, 0, 0);
            }

            $year = (int) $date->format('Y');
            if ($year < 1000 || $year > 3000) {
                \Log::warning("DateHelper::toJalali - Invalid year: $year");
                return null;
            }

            return Jalalian::fromCarbon($date)->format($format);
        } catch (Exception $e) {
            \Log::error("toJalali() error: " . $e->getMessage());
            return null;
        }
    }

    /** اعداد فارسی → انگلیسی */
    public static function convertPersianToEnglishNumbers(string $input): string
    {
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($persian, $english, $input);
    }

    /** اگر لازم شد: الان تهران با ساعت 12 ظهر (برای مقدارهای پیش‌فرض) */
    public static function nowTehranNoon(): Carbon
    {
        return Carbon::now(self::TZ_TEHRAN)->setTime(12, 0, 0);
    }

    public static function getLeadStatusLabel($status): string
    {
        return self::leadStatuses()[$status] ?? $status;
    }
}
