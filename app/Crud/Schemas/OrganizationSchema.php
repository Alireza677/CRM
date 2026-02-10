<?php

namespace App\Crud\Schemas;

use App\Models\Organization;
use App\Models\User;

class OrganizationSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'organizations',
            'title' => 'سازمان‌ها',
            'model' => Organization::class,
            'routes' => [
                'index' => 'sales.organizations.index',
                'create' => 'sales.organizations.create',
                'show' => 'sales.organizations.show',
                'edit' => 'sales.organizations.edit',
                'destroy' => 'sales.organizations.destroy',
                'bulkDestroy' => 'sales.organizations.bulkDelete',
            ],
            'per_page' => 25,
            'per_page_options' => [25, 50, 100, 250],
            'query' => function ($query, $request) {
                $user = $request->user();
                if ($user) {
                    $query->visibleFor($user, 'organizations');
                }

                $query->with(['contacts', 'opportunities', 'assignedUser']);
            },
            'columns' => [
                [
                    'key' => 'name',
                    'label' => 'نام سازمان',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'phone',
                    'label' => 'تلفن',
                    'type' => 'text',
                ],
                [
                    'key' => 'contacts',
                    'label' => 'مخاطب مرتبط',
                    'type' => 'text',
                    'format' => function ($row) {
                        $contact = $row->contacts?->first();
                        return $contact?->full_name ?: '—';
                    },
                ],
                [
                    'key' => 'city',
                    'label' => 'شهر',
                    'type' => 'text',
                ],
                [
                    'key' => 'state',
                    'label' => 'استان',
                    'type' => 'text',
                ],
                [
                    'key' => 'industry',
                    'label' => 'صنعت',
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
                    'key' => 'name',
                    'type' => 'text',
                    'placeholder' => 'نام سازمان',
                ],
                [
                    'key' => 'phone',
                    'type' => 'text',
                    'placeholder' => 'تلفن',
                ],
                [
                    'key' => 'contact',
                    'column' => 'contacts',
                    'type' => 'text',
                    'columns' => ['contacts.first_name', 'contacts.last_name', 'contacts.mobile'],
                    'placeholder' => 'مخاطب مرتبط',
                ],
                [
                    'key' => 'city',
                    'type' => 'text',
                    'placeholder' => 'شهر',
                ],
                [
                    'key' => 'state',
                    'type' => 'text',
                    'placeholder' => 'استان',
                ],
                [
                    'key' => 'industry',
                    'type' => 'text',
                    'placeholder' => 'صنعت',
                ],
                [
                    'key' => 'created_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ ایجاد',
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
            ],
            'show' => [
                'load' => ['contacts', 'opportunities', 'assignedUser'],
                'withCount' => ['contacts', 'opportunities', 'activities'],
                'tabs' => [
                    'summary' => [
                        'label' => 'خلاصه',
                        'view_mode' => 'cards',
                        'blocks' => [
                            [
                                'type' => 'stat',
                                'label' => 'مخاطبین',
                                'value' => function ($m) {
                                    return $m->contacts_count ?? 0;
                                },
                                'color' => 'bg-blue-50 text-blue-700',
                            ],
                            [
                                'type' => 'stat',
                                'label' => 'فرصت‌ها',
                                'value' => function ($m) {
                                    return $m->opportunities_count ?? 0;
                                },
                                'color' => 'bg-emerald-50 text-emerald-700',
                            ],
                            [
                                'type' => 'stat',
                                'label' => 'به‌روزرسانی‌ها',
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
                                        return 'نام: ' . ($m->name ?: '—');
                                    },
                                    function ($m) {
                                        return 'تلفن: ' . ($m->phone ?: '—');
                                    },
                                    function ($m) {
                                        return 'مسئول: ' . (optional($m->assignedUser)->name ?? '—');
                                    },
                                ],
                            ],
                        ],
                    ],
                    'info' => [
                        'label' => 'اطلاعات',
                        'view' => 'sales.organizations.tabs.info',
                    ],
                    'contacts' => [
                        'label' => 'مخاطبین',
                        'view_mode' => 'html',
                        'blocks' => [
                            [
                                'type' => 'html',
                                'html' => '<div class="text-sm text-gray-500">لیست مخاطبین سازمان در این بخش نمایش داده می‌شود.</div>',
                            ],
                        ],
                    ],
                    'opportunities' => [
                        'label' => 'فرصت‌ها',
                        'view' => 'sales.organizations.tabs.opportunities',
                    ],
                    'proformas' => [
                        'label' => 'پیش‌فاکتورها',
                        'view' => 'sales.organizations.tabs.proformas',
                        'data' => function ($m) {
                            $query = \App\Models\Proforma::query()
                                ->where('organization_id', $m->id);
                            $user = auth()->user();
                            if ($user) {
                                $query->visibleFor($user, 'proformas');
                            }

                            return [
                                'proformas' => $query->latest()->get(),
                            ];
                        },
                    ],
                    'notes' => [
                        'label' => 'یادداشت‌ها',
                        'view' => 'sales.organizations.tabs.notes',
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
                ],
            ],
        ];
    }
}
