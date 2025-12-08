<?php

return [
   'stages' => [
        'open'           => 'ایجاد شده',
        'in_progress'    => 'در حال پیگیری',
        'proposal_sent'  => 'ارسال پیش‌فاکتور',
        'won'            => 'برنده',
        'lost'           => 'بازنده',
    ],

    'lost_reasons' => [
        'price',
        'decision_delay',
        'competitor_capability',
        'no_budget',
        'requirement_changed',
        'internal_choice',
        'no_trust_in_brand',
    ],

    'closed_stages' => ['won', 'lost'],
];
