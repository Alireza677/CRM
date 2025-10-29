<?php

namespace App\Helpers;

class FormOptionsHelper
{
    // ---------------- Lead: Status & Source ----------------
    public static function getLeadStatusLabel($status): string
    {
        $statuses = self::leadStatuses();
        $status = strtolower(trim((string)$status));
        return $statuses[$status] ?? (string)$status;
    }

    public static function getLeadSourceLabel($source): string
    {
        $sources = self::leadSources();
        $source = strtolower(trim((string)$source));
        return $sources[$source] ?? (string)$source;
    }

    public static function leadStatuses(): array
    {
        return [
            'new' => 'جدید',
            'contacted' => 'تماس گرفته شده',
            'qualified' => 'واجد شرایط',
            'lost' => 'سرکاری',
        ];
    }

    public static function leadSources(): array
    {
        $src = [
            'website' => 'وب‌سایت',
            'phone' => 'تماس تلفنی',
            'referral' => 'معرفی',
            'event' => 'نمایشگاه / رویداد',
            'representative' => 'نماینده',
            'old_customer' => 'مشتری قدیمی',
        ];
        $src['tender'] = 'مناقصه';
        return $src;
    }

    // ---------------- Proforma: Stages ----------------
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
        $stage = strtolower(trim((string)$stage));
        return $stages[$stage] ?? (string)$stage;
    }

    // ---------------- Opportunity: Sources & Stages ----------------
    public static function opportunitySources(): array
    {
        // Base choices from lead sources
        $sources = self::leadSources();
        // Add extra option specific to opportunities
        $sources['in_person_marketing'] = 'بازاریابی حضوری';
        return $sources;
    }

    public static function getOpportunitySourceLabel($source): string
    {
        $sources = self::opportunitySources();
        $key = strtolower(trim((string)$source));
        return $sources[$key] ?? (string)$source;
    }

    public static function opportunityStages(): array
    {
        return [
            'new'           => 'جدید',
            'qualification' => 'ارزیابی صلاحیت',
            'qualified'     => 'واجد شرایط',
            'proposal'      => 'پیشنهاد/پیش‌فاکتور',
            'negotiation'   => 'مذاکره',
            'won'           => 'برنده',
            'lost'          => 'از دست رفته',
        ];
    }

    public static function getOpportunityStageLabel($stage): string
    {
        $stages = self::opportunityStages();
        $key = strtolower(trim((string)$stage));
        return $stages[$key] ?? (string)$stage;
    }

    // ---------------- Iran locations (subset) ----------------
    public static function iranLocations(): array
    {
        // برای اختصار چند شهر گذاشته شده؛ در صورت نیاز کامل‌تر کن.
        return [
            'تهران' => ['تهران','ری','اسلامشهر','قدس','ملارد','پردیس','بهارستان','پاکدشت','ورامین'],
            'البرز' => ['کرج','فردیس','نظرآباد','اشتهارد','ماهدشت'],
            'آذربایجان شرقی' => ['تبریز','مراغه','مرند','اهر','شبستر','بناب','سراب','میانه'],
            'آذربایجان غربی' => ['ارومیه','خوی','میاندوآب','بوکان','سلماس','مهاباد','سردشت','پیرانشهر','شاهین‌دژ'],
            'اصفهان' => ['اصفهان','کاشان','نجف‌آباد','خمینی‌شهر','شاهین‌شهر','زرین‌شهر','فولادشهر','مبارکه','گلپایگان'],
            'خراسان رضوی' => ['مشهد','نیشابور','سبزوار','تربت‌حیدریه','قوچان','کاشمر','تایباد','گناباد','سرخس'],
            'فارس' => ['شیراز','مرودشت','فسا','لار','جهرم','کازرون','داراب','آباده','اقلید'],
            'خوزستان' => ['اهواز','آبادان','خرمشهر','ماهشهر','دزفول','شوشتر','ایذه','بندر امام','بهبهان'],
            'گیلان' => ['رشت','انزلی','لاهیجان','لنگرود','تالش','صومعه‌سرا','فومن','آستارا'],
            'مازندران' => ['ساری','بابل','آمل','قائم‌شهر','نوشهر','چالوس','تنکابن','بابلسر','محمودآباد'],
            'کرمان' => ['کرمان','رفسنجان','جیرفت','سیرجان','بم','زرند','بردسیر','کهَنوج','راور','انار'],
            'یزد' => ['یزد','میبد','اردکان','ابرکوه','بافق'],
            'هرمزگان' => ['بندرعباس','قشم','کیش','میناب','بندرلنگه','جاسک','رودان'],
            'کرمانشاه' => ['کرمانشاه','اسلام‌آباد غرب','سنقر','هرسین','کنگاور','جوانرود'],
            'همدان' => ['همدان','ملایر','نهاوند','تويسرکان','اسدآباد','رزن'],
            'زنجان' => ['زنجان','ابهر','خرمدره','خدابنده','طارم'],
            'قزوین' => ['قزوین','البرز','تاکستان','آبیک','بوئین‌زهرا'],
            'قم' => ['قم'],
            'سمنان' => ['سمنان','شاهرود','دامغان','گرمسار','مهدی‌شهر'],
            'سیستان و بلوچستان' => ['زاهدان','چابهار','زابل','ایرانشهر','سراوان','خاش','کنارک'],
            'گلستان' => ['گرگان','گنبد','علی‌آباد','آق‌قلا','کلاله','مینودشت'],
            'اردبیل' => ['اردبیل','مشگین‌شهر','پارس‌آباد','خلخال','گرمی'],
            'ایلام' => ['ایلام','دهلران','آبدانان','ایوان','مهران'],
            'بوشهر' => ['بوشهر','برازجان','جم','گناوه','کنگان','دیر','دشتی'],
            'چهارمحال و بختیاری' => ['شهرکرد','بروجن','فارسان','لردگان','کوهرنگ'],
            'خراسان شمالی' => ['بجنورد','شیروان','اسفراین','آشخانه','جاجرم'],
            'خراسان جنوبی' => ['بیرجند','قائن','طبس','فردوس','نهبندان'],
            'کهگیلویه و بویراحمد' => ['یاسوج','دهدشت','گچساران','لیکک'],
            'لرستان' => ['خرم‌آباد','بروجرد','دورود','الیگودرز','کوهدشت','نورآباد'],
            'کردستان' => ['سنندج','سقز','بانه','مریوان','قروه','بیجار','کامیاران'],
            'مرکزی' => ['اراک','ساوه','خمین','محلات','شازند','دلیجان'],
        ];
    }

    public static function iranStates(): array
    {
        return array_keys(self::iranLocations());
    }
}
