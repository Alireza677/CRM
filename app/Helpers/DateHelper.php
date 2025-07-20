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
            $jalaliDate = str_replace('-', '/', $jalaliDate); // پشتیبانی از هر دو نوع جداکننده
            if (!preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $jalaliDate)) {
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
     * برچسب وضعیت سرنخ فروش
     */
    public static function getLeadStatusLabel($status): string
    {
        return self::leadStatuses()[$status] ?? $status;
    }
}
