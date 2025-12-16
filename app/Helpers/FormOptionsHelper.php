<?php

namespace App\Helpers;

class FormOptionsHelper
{
    // ---------------- Permission scopes (role matrix & reports) ----------------
    public static function permissionScopes(): array
    {
        // Supported scopes that are backed by existing permissions/policies
        return ['own', 'team', 'department', 'company'];
    }

  public static function permissionScopeLabels(): array
{
    return [
        'own'        => 'شخصی',
        'team'       => 'تیمی',
        'department' => 'دپارتمان',
        'company'    => 'سازمانی',
    ];
}

    // ---------------- Lead: Status & Source ----------------
    public static function getLeadStatusLabel($status): string
    {
        $statuses = self::leadStatuses();
        $status = strtolower(trim((string)$status));
        $aliasMap = [
            'converted' => 'converted_to_opportunity',
            'junk'      => 'discarded',
        ];

        $lookupKey = $aliasMap[$status] ?? $status;

        return $statuses[$lookupKey] ?? $statuses[$status] ?? (string)$status;
    }

    public static function getLeadSourceLabel($source): string
    {
        $sources = self::leadSources();
        $source = strtolower(trim((string)$source));
        return $sources[$source] ?? (string)$source;
    }

    public static function leadStatuses(): array
{
    // فقط ۴ وضعیت نهایی را از config بخوان
    $configured = config('lead.statuses', []);

    // اگر config خالی بود، این مقادیر را پیش‌فرض بگذار
    $statuses = !empty($configured) ? $configured : [
        'new'       => 'جدید',
        'contacted' => 'تماس گرفته شده',
        'converted' => 'تبدیل شده به فرصت',
        'discarded' => 'سرکاری / حذف شده',
    ];

    // هیچ alias اضافه نکن — نمایش باید فقط همین ۴ گزینه باشد
    return $statuses;
}



public static function leadDisqualifyReasons(): array
{
    $configured = config('lead.disqualify_reasons', []);

    // اگر از config آمده (کلیدها)
    if (!empty($configured)) {
        return collect($configured)->mapWithKeys(function ($key) {
            return [$key => __('lead.disqualify_reasons.' . $key)];
        })->toArray();
    }

    // حالت پیش‌فرض
    $defaults = [
        'no_need',
        'no_budget',
        'not_decision_maker',
        'competitor_price',
        'wrong_or_duplicate',
        'out_of_scope',
        'unrealistic_timing',
    ];

    return collect($defaults)->mapWithKeys(function ($key) {
        return [$key => __('lead.disqualify_reasons.' . $key)];
    })->toArray();
}


    public static function leadSources(): array
    {
        $src = [
            'website'        => 'وب‌سایت',
            'phone'          => 'تماس تلفنی',
            'referral'       => 'معرفی',
            'event'          => 'نمایشگاه / رویداد',
            'representative' => 'نماینده',
            'old_customer'   => 'مشتری قدیمی',
        ];
        $src['tender']  = 'مناقصه';
        $src['contact'] = 'مخاطبین';
        return $src;
    }

    // ---------------- Proforma: Stages ----------------
    public static function proformaStages(): array
    {
        return [
            'draft'                  => 'پیش‌نویس',
            'send_for_approval'      => 'ارسال برای تاییدیه',
            'awaiting_second_approval' => 'در انتظار تایید نهایی',
            'approved'               => 'تایید شده',
            'rejected'               => 'رد شده',
            'finalized'              => 'نهایی شده',
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
    $configured = config('opportunity.stages', []);
    if (!empty($configured)) {
        return $configured;
    }

    return [
        'open'          => 'ایجاد شده',
        'proposal_sent' => 'پیشنهاد ارسال شده',
        'negotiation'   => 'در حال مذاکره',
        'won'           => 'برنده ',
        'lost'          => 'از دست رفته',
        
    ];
}


    public static function getOpportunityStageLabel($stage): string
    {
        $stages = self::opportunityStages();
        $key = strtolower(trim((string)$stage));
        return $stages[$key] ?? (string)$stage;
    }

    public static function opportunityLostReasons(): array
    {
        $configured = config('opportunity.lost_reasons', []);
        if (!empty($configured)) {
            return array_combine($configured, $configured);
        }

        return [
            'price'                  => 'price',
            'decision_delay'         => 'decision_delay',
            'competitor_capability'  => 'competitor_capability',
            'no_budget'              => 'no_budget',
            'requirement_changed'    => 'requirement_changed',
            'internal_choice'        => 'internal_choice',
            'no_trust_in_brand'      => 'no_trust_in_brand',
        ];
    }

    // ---------------- Iran locations (subset) ----------------
    public static function iranLocations(): array
    {
        // برای اختصار چند شهر گذاشته شده؛ در صورت نیاز کامل‌تر کن.
        return [
    'تهران' => [
        'تهران','ری','اسلامشهر','قدس','ملارد','پردیس','بهارستان','پاکدشت','ورامین',
        'قرچک','باقرشهر','شاهدشهر','صالح‌آباد','نسیم‌شهر','چهاردانگه','شمیرانات','لواسان','فشم'
    ],

    'البرز' => [
        'کرج','فردیس','نظرآباد','اشتهارد','ماه‌دشت','محمدشهر','مشکین‌دشت','کوهسار','طالقان','چهارباغ'
    ],

    'آذربایجان شرقی' => [
        'تبریز','مراغه','مرند','اهر','شبستر','بناب','سراب','میانه','هشترود','آذرشهر',
        'ملکان','جلفا','بستان‌آباد','ورزقان','عجب‌شیر','اسکو','کلیبر','خسروشهر'
    ],

    'آذربایجان غربی' => [
        'ارومیه','خوی','میاندوآب','بوکان','سلماس','مهاباد','سردشت','پیرانشهر','شاهین‌دژ',
        'نقده','اشنویه','چایپاره','تکاب','سیه‌چشمه'
    ],

    'اصفهان' => [
        'اصفهان','کاشان','نجف‌آباد','خمینی‌شهر','شاهین‌شهر','زرین‌شهر','فولادشهر',
        'مبارکه','گلپایگان','فریدن','اردستان','سمیرم','خوانسار','دهاقان','نایین','هرند'
    ],

    'خراسان رضوی' => [
        'مشهد','نیشابور','سبزوار','تربت‌حیدریه','قوچان','کاشمر','تایباد','گناباد','سرخس',
        'چناران','درگز','فریمان','رشتخوار','بردسکن','طرقبه','شاندیز'
    ],

    'فارس' => [
        'شیراز','مرودشت','فسا','لار','جهرم','کازرون','داراب','آباده','اقلید','سپیدان',
        'فیروزآباد','لامرد','نورآباد','نی‌ریز','استهبان','قیر و کارزین'
    ],

    'خوزستان' => [
        'اهواز','آبادان','خرمشهر','ماهشهر','دزفول','شوشتر','ایذه','بندر امام','بهبهان',
        'شوش','حصیرآباد','رامهرمز','اندیمشک','حمیدیه','شادگان','هندیجان'
    ],

    'گیلان' => [
        'رشت','انزلی','لاهیجان','لنگرود','تالش','صومعه‌سرا','فومن','آستارا','رودسر','املش'
    ],

    'مازندران' => [
        'ساری','بابل','آمل','قائم‌شهر','نوشهر','چالوس','تنکابن','بابلسر','محمودآباد',
        'بهشهر','رامسر','جویبار','کلاردشت'
    ],

    'کرمان' => [
        'کرمان','رفسنجان','جیرفت','سیرجان','بم','زرند','بردسیر','کهنوج','راور','انار',
        'فهرج','رودبار جنوب'
    ],

    'یزد' => [
        'یزد','میبد','اردکان','ابرکوه','بافق','مهریز','اشکذر','تفت'
    ],

    'هرمزگان' => [
        'بندرعباس','قشم','کیش','میناب','بندرلنگه','جاسک','رودان','حاجی‌آباد','پارسیان'
    ],

    'کرمانشاه' => [
        'کرمانشاه','اسلام‌آباد غرب','سنقر','هرسین','کنگاور','جوانرود','پاوه','صحنه'
    ],

    'همدان' => [
        'همدان','ملایر','نهاوند','تویسرکان','اسدآباد','رزن','بهار','فامنین'
    ],

    'زنجان' => [
        'زنجان','ابهر','خرمدره','خدابنده','طارم','ماهنشان'
    ],

    'قزوین' => [
        'قزوین','البرز','تاکستان','آبیک','بوئین‌زهرا','سیردان','محمدیه'
    ],

    'قم' => [
        'قم'
    ],

    'سمنان' => [
        'سمنان','شاهرود','دامغان','گرمسار','مهدی‌شهر','ایوانکی'
    ],

    'سیستان و بلوچستان' => [
        'زاهدان','چابهار','زابل','ایرانشهر','سراوان','خاش','کنارک','میرجاوه','دلگان'
    ],

    'گلستان' => [
        'گرگان','گنبد','علی‌آباد','آق‌قلا','کلاله','مینودشت','رامیان','گمیشان'
    ],

    'اردبیل' => [
        'اردبیل','مشگین‌شهر','پارس‌آباد','خلخال','گرمی','نمین','نیر'
    ],

    'ایلام' => [
        'ایلام','دهلران','آبدانان','ایوان','مهران','دره‌شهر','چرداول'
    ],

    'بوشهر' => [
        'بوشهر','برازجان','جم','گناوه','کنگان','دیر','دشتی','خورموج','عسلویه'
    ],

    'چهارمحال و بختیاری' => [
        'شهرکرد','بروجن','فارسان','لردگان','کوهرنگ','سامان'
    ],

    'خراسان شمالی' => [
        'بجنورد','شیروان','اسفراین','آشخانه','جاجرم','گرمه'
    ],

    'خراسان جنوبی' => [
        'بیرجند','قائن','طبس','فردوس','نهبندان','سرایان'
    ],

    'کهگیلویه و بویراحمد' => [
        'یاسوج','دهدشت','گچساران','لیکک','سی‌سخت'
    ],

    'لرستان' => [
        'خرم‌آباد','بروجرد','دورود','الیگودرز','کوهدشت','نورآباد','ازنا','پلدختر'
    ],

    'کردستان' => [
        'سنندج','سقز','بانه','مریوان','قروه','بیجار','کامیاران','دیواندره'
    ],

    'مرکزی' => [
        'اراک','ساوه','خمین','محلات','شازند','دلیجان','آشتیان','تفرش'
    ],
];

    }

    public static function iranStates(): array
    {
        return array_keys(self::iranLocations());
    }
}
