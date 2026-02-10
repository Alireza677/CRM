<?php

namespace App\Crud\Schemas;

use App\Models\Contact;
use App\Models\User;
use App\Models\Organization;

class ContactSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'contacts',
            'title' => 'مخاطبین',
            'model' => Contact::class,
            'routes' => [
                'index' => 'sales.contacts.index',
                'create' => 'sales.contacts.create',
                'show' => 'sales.contacts.show',
                'edit' => 'sales.contacts.edit',
                'destroy' => 'sales.contacts.destroy',
                'bulkDestroy' => 'sales.contacts.bulk_delete',
            ],
            'per_page' => 25,
            'per_page_options' => [25, 50, 100, 200],
            'query' => function ($query, $request) {
                $user = $request->user();
                if ($user) {
                    $query->visibleFor($user, 'contacts');
                }

                $query->with(['organization', 'assignedUser']);
            },
            'columns' => [
                [
                    'key' => 'full_name',
                    'label' => 'نام',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'mobile',
                    'label' => 'موبایل',
                    'type' => 'text',
                ],
                [
                    'key' => 'position',
                    'label' => 'سمت',
                    'type' => 'text',
                ],
                [
                    'key' => 'organization.name',
                    'label' => 'سازمان',
                    'type' => 'relation',
                    'width' => 'w-64',
                ],
                [
                    'key' => 'state',
                    'label' => 'استان',
                    'type' => 'text',
                ],
                [
                    'key' => 'city',
                    'label' => 'شهر',
                    'type' => 'text',
                ],
                [
                    'key' => 'assignedUser.name',
                    'label' => 'مسئول',
                    'type' => 'relation',
                ],
                [
                    'key' => 'created_at',
                    'label' => 'تاریخ ایجاد',
                    'type' => 'date',
                    'sortable' => true,
                    'width' => 'w-32',
                ],
                [
                    'key' => 'actions',
                    'label' => 'عملیات',
                    'type' => 'actions',
                ],
            ],
            'filters' => [
                [
                    'key' => 'search',
                    'column' => 'full_name',
                    'columns' => ['first_name', 'last_name', 'mobile'],
                    'type' => 'text',
                    'placeholder' => 'نام/موبایل',
                ],
                [
                    'key' => 'mobile',
                    'type' => 'text',
                    'placeholder' => 'موبایل',
                ],
                [
                    'key' => 'organization',
                    'column' => 'organization.name',
                    'type' => 'select',
                    'options' => function () {
                        return Organization::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'سازمان',
                ],
                [
                    'key' => 'state',
                    'type' => 'text',
                    'placeholder' => 'استان',
                ],
                [
                    'key' => 'city',
                    'type' => 'text',
                    'placeholder' => 'شهر',
                ],
                [
                    'key' => 'assigned_to',
                    'column' => 'assignedUser.name',
                    'type' => 'select',
                    'options' => function () {
                        return User::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'مسئول',
                ],
                [
                    'key' => 'created_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ ایجاد',
                ],
            ],
            'show' => [
                'load' => ['organization', 'assignedUser', 'opportunities', 'proformas'],
                'withCount' => ['activities', 'opportunities', 'proformas'],
                'tabs' => [
                    'summary' => [
                        'label' => 'خلاصه',
                        'view_mode' => 'cards',
                        'blocks' => [
                            [
                                'type' => 'stat',
                                'label' => 'فرصت‌ها',
                                'value' => function ($m) {
                                    return $m->opportunities_count ?? 0;
                                },
                                'color' => 'bg-blue-50 text-blue-700',
                            ],
                            [
                                'type' => 'stat',
                                'label' => 'پیش‌فاکتورها',
                                'value' => function ($m) {
                                    return $m->proformas_count ?? 0;
                                },
                                'color' => 'bg-emerald-50 text-emerald-700',
                            ],
                            [
                                'type' => 'stat',
                                'label' => 'فعالیت‌ها',
                                'value' => function ($m) {
                                    return $m->activities_count ?? 0;
                                },
                                'color' => 'bg-amber-50 text-amber-700',
                            ],
                            [
                                'type' => 'card',
                                'title' => 'اطلاعات اصلی',
                                'lines' => [
                                    function ($m) {
                                        return 'نام: ' . ($m->full_name ?: '—');
                                    },
                                    function ($m) {
                                        return 'موبایل: ' . ($m->mobile ?: '—');
                                    },
                                    function ($m) {
                                        return 'ایمیل: ' . ($m->email ?: '—');
                                    },
                                ],
                            ],
                        ],
                    ],
                    'info' => [
                        'label' => 'اطلاعات',
                        'view' => 'sales.contacts.tabs.info',
                    ],
                    'notes' => [
                        'label' => 'یادداشت‌ها',
                        'view' => 'sales.contacts.tabs.notes',
                        'data' => function () {
                            return [
                                'allUsers' => \App\Models\User::whereNotNull('username')->get(),
                            ];
                        },
                    ],
                    'updates' => [
                        'label' => 'به‌روزرسانی‌ها',
                        'view_mode' => 'html',
                        'blocks' => [
                            [
                                'type' => 'html',
                                'html' => '<div class="text-sm text-gray-500">به‌روزرسانی برای نمایش وجود ندارد.</div>',
                            ],
                        ],
                    ],
                    'opportunities' => [
                        'label' => 'فرصت‌ها',
                        'view' => 'sales.contacts.tabs.opportunities',
                    ],
                    'leads' => [
                        'label' => 'سرنخ‌ها',
                        'view' => 'sales.contacts.tabs.leads',
                        'data' => function ($m) {
                            return [
                                'leads' => $m->leads()
                                    ->visibleFor(auth()->user(), 'leads')
                                    ->latest()
                                    ->get(),
                            ];
                        },
                    ],
                    'proformas' => [
                        'label' => 'پیش‌فاکتورها',
                        'view' => 'sales.contacts.tabs.proformas',
                    ],
                    'files' => [
                        'label' => 'فایل‌ها',
                        'view_mode' => 'html',
                        'blocks' => [
                            [
                                'type' => 'html',
                                'html' => '<div class="text-sm text-gray-500">فایلی برای نمایش وجود ندارد.</div>',
                            ],
                        ],
                    ],
                    'activities' => [
                        'label' => 'فعالیت‌ها',
                        'view_mode' => 'html',
                        'blocks' => [
                            [
                                'type' => 'html',
                                'html' => '<div class="text-sm text-gray-500">فعالیت‌ها در این بخش نمایش داده می‌شوند.</div>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
