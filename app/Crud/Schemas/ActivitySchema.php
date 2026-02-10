<?php

namespace App\Crud\Schemas;

use App\Models\Activity;
use App\Models\User;

class ActivitySchema
{
    public static function schema(): array
    {
        return [
            'key' => 'activities',
            'title' => 'فعالیت‌ها',
            'model' => Activity::class,
            'routes' => [
                'index' => 'activities.index',
                'create' => 'activities.create',
                'show' => 'activities.show',
                'edit' => 'activities.edit',
                'destroy' => 'activities.destroy',
            ],
            'per_page' => 20,
            'per_page_options' => [20, 50, 100],
            'query' => function ($query, $request) {
                $query->with([
                    'assignedTo:id,name',
                    'related',
                ]);

                // Hide system-generated logs from task list.
                $query->whereNotIn('subject', [
                    'proforma_created',
                    'lead_status_reason',
                    'lost_reason',
                ]);

                if (!$request->filled('sort')) {
                    $query->orderByDesc('start_at')
                        ->orderByDesc('id');
                }
            },
            'columns' => [
                [
                    'key' => 'subject',
                    'label' => 'موضوع',
                    'type' => 'html',
                    'sortable' => true,
                    'format' => function ($row) {
                        $subject = $row->subject ?: 'بدون عنوان';
                        $url = route('activities.show', $row->id);
                        $html = '<a href="' . $url . '" class="text-indigo-600 hover:text-indigo-800">' . e($subject) . '</a>';
                        if (!empty($row->is_private)) {
                            $html .= ' <span class="text-[11px] bg-zinc-100 text-zinc-700 rounded px-2 py-0.5">خصوصی</span>';
                        }
                        return $html;
                    },
                ],
                [
                    'key' => 'status',
                    'label' => 'وضعیت',
                    'type' => 'badge',
                    'sortable' => true,
                    'badges' => [
                        'not_started' => 'bg-gray-100 text-gray-700',
                        'in_progress' => 'bg-blue-100 text-blue-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'scheduled' => 'bg-purple-100 text-purple-700',
                    ],
                    'format' => function ($row, $raw) {
                        $map = [
                            'not_started' => 'شروع نشده',
                            'in_progress' => 'در حال انجام',
                            'completed' => 'تکمیل شده',
                            'scheduled' => 'برنامه‌ریزی شده',
                        ];
                        return $map[$raw] ?? '—';
                    },
                ],
                [
                    'key' => 'priority',
                    'label' => 'اولویت',
                    'type' => 'badge',
                    'sortable' => true,
                    'badges' => [
                        'normal' => 'bg-gray-100 text-gray-700',
                        'medium' => 'bg-amber-100 text-amber-700',
                        'high' => 'bg-red-100 text-red-700',
                    ],
                    'format' => function ($row, $raw) {
                        $map = [
                            'normal' => 'معمولی',
                            'medium' => 'متوسط',
                            'high' => 'زیاد',
                        ];
                        return $map[$raw] ?? '—';
                    },
                ],
                [
                    'key' => 'assignedTo.name',
                    'label' => 'ارجاع به',
                    'type' => 'relation',
                ],
                [
                    'key' => 'related',
                    'label' => 'مربوط به',
                    'type' => 'relation',
                    'format' => function ($row) {
                        return $row->related_name
                            ?? optional($row->related)->name
                            ?? optional($row->related)->title
                            ?? '—';
                    },
                ],
                [
                    'key' => 'start_at',
                    'label' => 'شروع',
                    'type' => 'datetime',
                    'sortable' => true,
                    'width' => 'w-36',
                ],
                [
                    'key' => 'due_at',
                    'label' => 'موعد',
                    'type' => 'datetime',
                    'sortable' => true,
                    'width' => 'w-36',
                ],
                [
                    'key' => 'actions',
                    'label' => 'عملیات',
                    'type' => 'actions',
                    'width' => 'w-40',
                ],
            ],
            'filters' => [
                [
                    'key' => 'q',
                    'column' => 'subject',
                    'columns' => ['subject', 'description'],
                    'type' => 'text',
                    'placeholder' => 'جست‌وجو در موضوع',
                ],
                [
                    'key' => 'status',
                    'column' => 'status',
                    'type' => 'select',
                    'options' => [
                        'not_started' => 'شروع نشده',
                        'in_progress' => 'در حال انجام',
                        'completed' => 'تکمیل شده',
                        'scheduled' => 'برنامه‌ریزی شده',
                    ],
                    'placeholder' => 'وضعیت',
                ],
                [
                    'key' => 'priority',
                    'column' => 'priority',
                    'type' => 'select',
                    'options' => [
                        'normal' => 'معمولی',
                        'medium' => 'متوسط',
                        'high' => 'زیاد',
                    ],
                    'placeholder' => 'اولویت',
                ],
                [
                    'key' => 'assigned_to_id',
                    'column' => 'assignedTo.name',
                    'type' => 'select',
                    'options' => function () {
                        return User::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'ارجاع به',
                ],
                [
                    'key' => 'start_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ شروع',
                ],
                [
                    'key' => 'due_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ موعد',
                ],
            ],
        ];
    }
}
