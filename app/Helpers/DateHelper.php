<?php

namespace App\Helpers;

use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
use Exception;

class DateHelper
{
    /**
     * تبدیل تاریخ شمسی به میلادی (خروجی: Y-m-d)
     */
    public static function toGregorian(string $jalaliDate): ?string
    {
        try {
            // پشتیبانی از اعداد فارسی و جداکننده -
            $jalaliDate = self::convertPersianToEnglishNumbers($jalaliDate);
            $jalaliDate = str_replace('-', '/', $jalaliDate);

            if (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $jalaliDate)) {
                \Log::warning("❗ Invalid Jalali date format after cleanup: " . $jalaliDate);
                return null;
            }

            return Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon()->format('Y-m-d');
        } catch (Exception $e) {
            \Log::error("toGregorian() error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    public static function toJalali($date, $format = 'Y/m/d'): ?string
    {
        try {
            if (empty($date)) {
                return null;
            }

            if (!$date instanceof Carbon) {
                $date = Carbon::parse($date);
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

    /**
     * تبدیل اعداد فارسی به انگلیسی
     */
    public static function convertPersianToEnglishNumbers(string $input): string
    {
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($persian, $english, $input);
    }

    /**
     * برچسب وضعیت سرنخ فروش
     */
    public static function getLeadStatusLabel($status): string
    {
        return self::leadStatuses()[$status] ?? $status;
    }
}
