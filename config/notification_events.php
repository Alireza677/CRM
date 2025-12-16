<?php

return [
    'channels' => [
        'database' => 'داخلی (سیستم)',
        'email'    => 'ایمیل',
        // اگر SMS هم داری، این خط را باز کن:
        'sms'      => 'SMS',
    ],

    'modules' => [
        'purchase_orders' => [
            'label' => 'سفارش‌های خرید',
            'events' => [
                'status.changed' => [
                    'label' => 'تغییر وضعیت',
                    'default_channels' => ['database'],
                    'placeholders' => ['{po_number}', '{po_subject}', '{from_status}', '{to_status}', '{requester_name}'],
                    'default_subject' => 'تغییر وضعیت سفارش خرید {po_number}',
                    'default_body' => 'وضعیت سفارش خرید {po_number} از {from_status} به {to_status} تغییر یافت. درخواست‌کننده: {requester_name}',
                    // اگر برای این رویداد هم ویرایش قالب می‌خواهی:
                    'supports' => [
                        'channel_templates' => true,
                        'conditions' => true,
                        'multiple_rules' => true,
                    ],
                ],
                'ready_for_delivery' => [
                    'label' => 'آماده برای تحویل به انبار',
                    'default_channels' => ['database','email'],
                    'placeholders' => ['{po_number}', '{po_subject}', '{requester_name}'],
                    'default_subject' => 'سفارش خرید {po_number} تأیید شد',
                    'default_body' => 'سفارش خرید {po_number} شما تأیید شد. لطفاً وضعیت را به «تحویل به انبار» تغییر دهید.',
                    'supports' => ['channel_templates' => true, 'conditions' => false],
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
                    // >>> اضافه کن
                    'supports' => [
                        'channel_templates' => true,
                        'conditions' => false,
                    ],
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
                    // >>> اگر می‌خواهی متنش را در مودال ذخیره کنی:
                    'supports' => [
                        'channel_templates' => true,
                        'conditions' => false,
                    ],
                ],
            ],
        ],

        'notes' => [
            'label' => 'یادداشت‌ها',
            'events' => [
                'note.mentioned' => [
                    'label' => 'منشن در یادداشت',
                    'default_channels' => ['database'],
                    'placeholders' => ['{note_excerpt}', '{mentioned_user}', '{context}', '{form_title}'],
                    'default_subject' => '{mentioned_user} در یادداشت منشن شد',
                    'default_body' => 'در {context} شما منشن شدید: {note_excerpt}',
                    // اختیاری: اگر می‌خواهی قابل ویرایش شود
                    'supports' => ['channel_templates' => true, 'conditions' => false],
                ],
            ],
        ],

        'reports' => [
            'label' => 'گزارش‌ها',
            'events' => [
                'scheduled.sent' => [
                    'label' => 'ارسال گزارش زمان‌بندی‌شده',
                    'default_channels' => ['email'],
                    'placeholders' => ['{report_title}'],
                    'default_subject' => 'گزارش زمان‌بندی شده: {report_title}',
                    'default_body' => 'گزارش زمان‌بندی شده شما آماده است. برای مشاهده گزارش به لینک مراجعه کنید.',
                    'supports' => [
                        'channel_templates' => true,
                        'conditions' => false,
                    ],
                ],
            ],
        ],

        'activities' => [
            'label' => 'وظایف',
            'events' => [
                'reminder.due' => [
                    'label' => 'یادآوری موعد وظیفه',
                    'default_channels' => ['database'],
                    'placeholders' => ['{activity_subject}', '{due_at}'],
                    'default_subject' => 'یادآوری وظیفه: {activity_subject}',
                    'default_body' => 'موعد: {due_at}',
                    'supports' => [
                        'channel_templates' => true,
                        'conditions' => false,
                    ],
                ],
            ],
        ],

        'emails' => [
            'label' => 'ایمیل‌ها',
            'events' => [
                'received' => [
                    'label' => 'دریافت ایمیل جدید',
                    'default_title' => 'ایمیل جدید دارید',
                    'default_channels' => ['database'],
                    'placeholders' => ['{email_subject}', '{from_name}', '{from_email}', '{received_at}'],
                    'default_subject' => 'ایمیل جدید دارید: {email_subject}',
                    'default_body' => "ایمیل جدید از {from_name} ({from_email}) با موضوع {email_subject}\nزمان دریافت: {received_at}\n{url}",
                    'supports' => [
                        'channel_templates' => true,
                        'conditions' => false,
                        'multiple_rules' => false,
                    ],
                ],
            ],
        ],
    ],
];
