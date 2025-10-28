<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationRule;

class NotificationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'module' => 'purchase_orders',
                'event'  => 'status.changed',
                'enabled' => true,
                'channels' => ['database'],
                'conditions' => [
                    'from_status' => '',
                    'to_status'   => '',
                ],
                'subject' => 'تغییر وضعیت سفارش خرید {po_number}',
                'body'    => "وضعیت سفارش از {from_status} به {to_status} تغییر یافت.\nتوسط {{ actor.name }}\nمشاهده: {{ url }}",
            ],
            [
                'module' => 'proformas',
                'event'  => 'approval.sent',
                'enabled' => true,
                'channels' => ['database','email'],
                'conditions' => null,
                'subject' => 'پیش‌فاکتور {proforma_number} برای تأیید ارسال شد',
                'body'    => "پیش‌فاکتور {proforma_number} مربوط به {customer_name} برای تأیید ارسال شد.\nارسال‌کننده: {{ actor.name }}\nلینک: {{ url }}",
            ],
            [
                'module' => 'leads',
                'event'  => 'assigned.changed',
                'enabled' => true,
                'channels' => ['database'],
                'conditions' => null,
                'subject' => 'ارجاع سرنخ {lead_name} تغییر کرد',
                'body'    => "ارجاع از {old_user} به {new_user} انجام شد.\nتوسط {{ actor.name }}\nمشاهده: {{ url }}",
            ],
            [
                'module' => 'notes',
                'event'  => 'note.mentioned',
                'enabled' => true,
                'channels' => ['database'],
                'conditions' => null,
                'subject' => '{mentioned_user} در یادداشت منشن شد',
                'body'    => "در {context} شما منشن شدید: {note_excerpt}\nمشاهده: {{ url }}",
            ],
        ];

        foreach ($defaults as $d) {
            NotificationRule::updateOrCreate(
                ['module' => $d['module'], 'event' => $d['event']],
                [
                    'enabled' => $d['enabled'],
                    'channels' => $d['channels'],
                    'conditions' => $d['conditions'],
                    'subject_template' => $d['subject'],
                    'body_template' => $d['body'],
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}

