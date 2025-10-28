<?php

return [
    // Supported channels globally
    'channels' => [
        'database' => 'داخلی (سیستم)',
        'email'    => 'ایمیل',
    ],

    // Modules and their events
    'modules' => [
        'purchase_orders' => [
            'label' => 'سفارش‌های خرید',
            'events' => [
                'status.changed' => [
                    'label' => 'تغییر وضعیت',
                    'default_channels' => ['database'],
                    'placeholders' => ['{po_number}', '{from_status}', '{to_status}', '{requester_name}'],
                    'default_subject' => 'تغییر وضعیت سفارش خرید {po_number}',
                    'default_body' => 'وضعیت سفارش خرید {po_number} از {from_status} به {to_status} تغییر یافت. درخواست‌کننده: {requester_name}',
                ],
            ],
        ],
        'proformas' => [
            'label' => 'پیش‌فاکتورها',
            'events' => [
                'approval.sent' => [
                    'label' => 'ارسال برای تأیید',
                    'default_channels' => ['database', 'email'],
                    'placeholders' => ['{proforma_number}', '{approver_name}', '{customer_name}'],
                    'default_subject' => 'پیش‌فاکتور {proforma_number} برای تأیید ارسال شد',
                    'default_body' => 'پیش‌فاکتور {proforma_number} مرتبط با {customer_name} برای {approver_name} ارسال شد.',
                ],
            ],
        ],
        'leads' => [
            'label' => 'سرنخ‌ها',
            'events' => [
                'assigned.changed' => [
                    'label' => 'تغییر ارجاع',
                    'default_channels' => ['database'],
                    'placeholders' => ['{lead_name}', '{old_user}', '{new_user}'],
                    'default_subject' => 'ارجاع سرنخ {lead_name} تغییر کرد',
                    'default_body' => 'ارجاع سرنخ {lead_name} از {old_user} به {new_user} تغییر یافت.',
                ],
            ],
        ],
        'notes' => [
            'label' => 'یادداشت‌ها',
            'events' => [
                'note.mentioned' => [
                    'label' => 'منشن در یادداشت',
                    'default_channels' => ['database'],
                    'placeholders' => ['{note_excerpt}', '{mentioned_user}', '{context}'],
                    'default_subject' => '{mentioned_user} در یادداشت منشن شد',
                    'default_body' => 'در {context} شما منشن شدید: {note_excerpt}',
                ],
            ],
        ],
    ],
];

