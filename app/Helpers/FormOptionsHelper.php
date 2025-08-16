<?php

namespace App\Helpers;

class FormOptionsHelper
{
    public static function getLeadStatusLabel($status): string
    {
        $statuses = self::leadStatuses();
        $status = strtolower(trim($status));
        return $statuses[$status] ?? $status;
    }

    public static function getLeadSourceLabel($source): string
    {
        $sources = self::leadSources();
        $source = strtolower(trim($source));
        return $sources[$source] ?? $source;
    }

    public static function leadStatuses(): array
    {
        return [
            'new' => 'جدید',
            'contacted' => 'تماس گرفته شده',
            'qualified' => 'واجد شرایط',
            'lost' => 'از دست رفته',
        ];
    }

    public static function leadSources(): array
    {
        return [
            'website' => 'وب‌سایت',
            'phone' => 'تماس تلفنی',
            'referral' => 'معرفی',
            'event' => 'نمایشگاه / رویداد',
            'exhibition' => 'نمایشگاه', // اگر به‌صورت خاص این مقدار استفاده شده
            'old_customer' => 'مشتری قدیمی', // برای پشتیبانی از منابع قدیمی‌تر
        ];
    }

    public static function proformaStages(): array
    {
        return [
            'draft' => 'پیش‌نویس',
            'send_for_approval' => 'ارسال برای تاییدیه',
            'awaiting_second_approval' => 'در انتظار تایید نهایی',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'finalized' => 'نهایی شده',
        ];
    }

    public static function getProformaStageLabel($stage): string
    {
        $stages = self::proformaStages();
        $stage = strtolower(trim($stage));
        return $stages[$stage] ?? $stage;
    }

}
