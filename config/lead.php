<?php

return [
    'statuses' => [
        'new'                      => 'جدید',
        'contacted'                => 'تماس گرفته شده',
        'qualified'                => 'واجد شرایط',
        'lost'                     => 'از دست رفته',
        'converted'                => 'تبدیل شده به فرصت',
        'converted_to_opportunity' => 'تبدیل شده به فرصت',
        'discarded'                => 'سرکاری',
    ],

    'disqualify_reasons' => [
        'no_need',
        'no_budget',
        'not_decision_maker',
        'competitor_price',
        'wrong_or_duplicate',
        'out_of_scope',
        'unrealistic_timing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Source Ownership
    |--------------------------------------------------------------------------
    |
    | Map lead sources to their ownership type. "company" sources route
    | the acquirer commission to the company user.
    |
    */
    'source_owners' => [
        'website' => 'company',
        'tender' => 'company',
        'event' => 'company',
    ],
];
