<?php

namespace App\Crud\Schemas;

use App\Helpers\FormOptionsHelper;
use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class LeadSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'leads',
            'title' => 'سرنخ‌های فروش',
            'model' => SalesLead::class,
            'routes' => [
                'index' => 'marketing.leads.index',
                'create' => 'marketing.leads.create',
                'show' => 'marketing.leads.show',
                'edit' => 'marketing.leads.edit',
                'destroy' => 'marketing.leads.destroy',
            ],
            'per_page' => 25,
            'per_page_options' => [25, 50, 100],
            'query' => function ($query, $request) {
                $user = $request->user();
                if ($user) {
                    $query->visibleFor($user, 'leads');
                }

                if ($request->routeIs('marketing.leads.favorites.index')) {
                    if ($user) {
                        $query->whereIn('id', DB::table('lead_favorites')
                            ->where('user_id', $user->id)
                            ->select('lead_id'));
                    } else {
                        $query->whereRaw('1=0');
                    }
                    $query->with(['assignedTo', 'contact']);
                } elseif ($request->routeIs('marketing.leads.converted')) {
                    $query->convertedListing()
                        ->with(['assignedTo', 'contact', 'convertedOpportunity']);
                } elseif ($request->routeIs('sales.leads.junk')) {
                    $query->junkListing()
                        ->with(['assignedTo', 'contact']);
                } else {
                    $query->activeListing()
                        ->with(['assignedTo', 'contact']);
                }

                $query->latest('created_at');
            },
            'columns' => [
                [
                    'key' => 'lead_number',
                    'label' => 'کد',
                    'type' => 'text',
                    'sortable' => true,
                    'width' => 'w-24',
                ],
                [
                    'key' => 'full_name',
                    'label' => 'نام/عنوان',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'company',
                    'label' => 'شرکت',
                    'type' => 'text',
                    'width' => 'w-40',
                ],
                [
                    'key' => 'mobile',
                    'label' => 'موبایل',
                    'type' => 'text',
                ],
                [
                    'key' => 'lead_status',
                    'label' => 'وضعیت',
                    'type' => 'badge',
                    'sortable' => true,
                    'format' => function ($row) {
                        return FormOptionsHelper::getLeadStatusLabel($row->lead_status ?? $row->status);
                    },
                    'badges' => [
                        'new' => 'bg-blue-50 text-blue-700',
                        'contacted' => 'bg-indigo-50 text-indigo-700',
                        'converted' => 'bg-emerald-50 text-emerald-700',
                        'discarded' => 'bg-rose-50 text-rose-700',
                        'lost' => 'bg-rose-50 text-rose-700',
                    ],
                ],
                [
                    'key' => 'lead_source',
                    'label' => 'منبع',
                    'type' => 'text',
                    'format' => function ($row) {
                        return FormOptionsHelper::getLeadSourceLabel($row->lead_source);
                    },
                ],
                [
                    'key' => 'assignedTo.name',
                    'label' => 'مسئول',
                    'type' => 'relation',
                ],
                [
                    'key' => 'created_at',
                    'label' => 'تاریخ ایجاد',
                    'type' => 'date',
                    'sortable' => true,
                    'width' => 'w-48',
                ],
                [
                    'key' => 'actions',
                    'label' => 'عملیات',
                    'type' => 'actions',
                ],
            ],
            'filters' => [
                [
                    'key' => 'full_name',
                    'type' => 'text',
                    'placeholder' => 'نام/عنوان',
                ],
                [
                    'key' => 'lead_status',
                    'type' => 'select',
                    'options' => [FormOptionsHelper::class, 'leadStatuses'],
                    'placeholder' => 'وضعیت',
                ],
                [
                    'key' => 'lead_source',
                    'type' => 'select',
                    'options' => [FormOptionsHelper::class, 'leadSources'],
                    'placeholder' => 'منبع',
                ],
                [
                    'key' => 'assigned_to',
                    'type' => 'select',
                    'options' => function () {
                        return User::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'مسئول',
                ],
                [
                    'key' => 'mobile',
                    'type' => 'text',
                    'placeholder' => 'موبایل',
                ],
                [
                    'key' => 'created_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ ایجاد',
                ],
            ],
            'show' => [
                'load' => ['contact', 'contacts', 'assignedTo'],
                'withCount' => ['notes', 'activities', 'contacts'],
                'tabs' => [
                    'overview' => [
                        'label' => 'خلاصه',
                        'view' => 'marketing.leads.tabs.overview',
                        'data' => function ($m) {
                            $rotationDueAt = $m->rotation_due_at;
                            $rotationRemainingSeconds = 0;
                            if ($rotationDueAt) {
                                $rotationRemainingSeconds = max(0, Carbon::now()->diffInSeconds($rotationDueAt, false));
                            }

                            return [
                                'rotationRemainingSeconds' => $rotationRemainingSeconds,
                            ];
                        },
                    ],
                    'info' => [
                        'label' => 'اطلاعات',
                        'view' => 'marketing.leads.tabs.info',
                    ],
                    'updates' => [
                        'label' => 'به‌روزرسانی‌ها',
                        'view' => 'marketing.leads.tabs.updates',
                    ],
                    'notes' => [
                        'label' => 'یادداشت‌ها',
                        'view' => 'marketing.leads.tabs.notes',
                        'data' => function () {
                            $allUsers = \App\Models\User::query()
                                ->select(['id', 'name', 'username'])
                                ->whereNotNull('username')
                                ->orderBy('name')
                                ->get();

                            return [
                                'allUsers' => $allUsers,
                            ];
                        },
                    ],
                    'contact' => [
                        'label' => 'مخاطب مرتبط',
                        'view' => 'marketing.leads.tabs.contact',
                        'data' => function () {
                            $contacts = \App\Models\Contact::select('id', 'first_name', 'last_name', 'mobile')
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get();

                            return [
                                'contacts' => $contacts,
                            ];
                        },
                    ],
                ],
            ],
        ];
    }
}
