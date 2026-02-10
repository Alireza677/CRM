<?php

namespace App\Crud\Schemas;

use App\Helpers\FormOptionsHelper;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Contact;
use App\Models\CommissionSetting;
use Spatie\Activitylog\Models\Activity;

class OpportunitySchema
{
    public static function schema(): array
    {
        return [
            'key' => 'opportunities',
            'title' => 'فرصت‌های فروش',
            'model' => Opportunity::class,
            'routes' => [
                'index' => 'sales.opportunities.index',
                'create' => 'sales.opportunities.create',
                'show' => 'sales.opportunities.show',
                'edit' => 'sales.opportunities.edit',
                'destroy' => 'sales.opportunities.destroy',
                'import' => 'sales.opportunities.import',
                'bulkDestroy' => 'sales.opportunities.bulk_delete',
            ],
            'per_page' => 25,
            'per_page_options' => [25, 50, 100],
            'query' => function ($query, $request) {
                $query->with(['contact', 'assignedUser']);

                if ($request->routeIs('sales.opportunities.favorites.index')) {
                    $user = $request->user();
                    if ($user) {
                        $query->whereIn('id', $user->favoriteOpportunities()->select('opportunity_id'));
                    } else {
                        $query->whereRaw('1=0');
                    }
                }
            },
            'columns' => [
                [
                    'key' => 'opportunity_number',
                    'label' => 'شماره',
                    'type' => 'text',
                    'sortable' => true,
                    'width' => 'w-28',
                ],
                [
                    'key' => 'name',
                    'label' => 'عنوان',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'contact.name',
                    'label' => 'مخاطب',
                    'type' => 'relation',
                ],
                [
                    'key' => 'stage',
                    'label' => 'مرحله',
                    'type' => 'badge',
                    'sortable' => true,
                    'format' => function ($row) {
                        return FormOptionsHelper::getOpportunityStageLabel($row->stage);
                    },
                    'badges' => (function () {
                        $badges = [];
                        foreach (FormOptionsHelper::opportunityStages() as $key => $label) {
                            $badges[$key] = 'bg-blue-50 text-blue-700';
                        }
                        $badges['won'] = 'bg-emerald-50 text-emerald-700';
                        $badges['lost'] = 'bg-rose-50 text-rose-700';
                        $badges['dead'] = 'bg-rose-50 text-rose-700';
                        return $badges;
                    })(),
                ],
                [
                    'key' => 'source',
                    'label' => 'منبع',
                    'type' => 'text',
                    'format' => function ($row) {
                        return FormOptionsHelper::getOpportunitySourceLabel($row->source);
                    },
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
                    'key' => 'opportunity_number',
                    'type' => 'text',
                    'placeholder' => 'شماره',
                ],
                [
                    'key' => 'name',
                    'type' => 'text',
                    'placeholder' => 'عنوان',
                ],
                [
                    'key' => 'contact',
                    'column' => 'contact.name',
                    'type' => 'text',
                    'relation' => 'contact',
                    'field' => 'name',
                    'placeholder' => 'مخاطب',
                ],
                [
                    'key' => 'stage',
                    'type' => 'select',
                    'options' => [FormOptionsHelper::class, 'opportunityStages'],
                    'placeholder' => 'مرحله',
                ],
                [
                    'key' => 'source',
                    'type' => 'select',
                    'options' => [FormOptionsHelper::class, 'opportunitySources'],
                    'placeholder' => 'منبع',
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
                'load' => ['contact', 'organization', 'assignedUser', 'roleAssignments.user', 'lastNote.user'],
                'withCount' => ['activities', 'notes', 'proformas', 'calls'],
                'tabs' => [
                    'summary' => [
                        'label' => 'خلاصه',
                        'view' => 'sales.opportunities.tabs.summary',
                        'data' => function (Opportunity $opportunity) {
                            $opportunity->loadMissing(['roleAssignments.user', 'primaryProforma']);

                            $rolePercents = CommissionSetting::resolveRolePercents();
                            $roles = [
                                [
                                    'type' => 'acquirer',
                                    'label' => 'جذب‌کننده',
                                    'percent' => (float) ($rolePercents['acquirer'] ?? 0),
                                ],
                                [
                                    'type' => 'relationship_owner',
                                    'label' => 'مالک فرصت',
                                    'percent' => (float) ($rolePercents['relationship_owner'] ?? 0),
                                ],
                                [
                                    'type' => 'closer',
                                    'label' => 'نهایی‌کننده',
                                    'percent' => (float) ($rolePercents['closer'] ?? 0),
                                ],
                                [
                                    'type' => 'execution_owner',
                                    'label' => 'پشتیبان فنی',
                                    'percent' => (float) ($rolePercents['execution_owner'] ?? 0),
                                ],
                            ];

                            $primaryProforma = $opportunity->resolvePrimaryProforma();
                            $baseAmount = $primaryProforma?->commission_base_amount;

                            $rows = [];
                            foreach ($roles as $role) {
                                $user = $opportunity->getRoleUser($role['type']);
                                $amount = is_null($baseAmount) ? null : ($baseAmount * $role['percent'] / 100);
                                $rows[] = [
                                    'role_label' => $role['label'],
                                    'user_name' => $user?->name ?? $user?->username ?? null,
                                    'percent' => (float) $role['percent'],
                                    'amount' => $amount,
                                ];
                            }

                            return ['commissionRows' => $rows];
                        },
                    ],
                    'info' => [
                        'label' => 'اطلاعات',
                        'view' => 'sales.opportunities.tabs.info',
                        'data' => function (Opportunity $opportunity) {
                            $opportunity->loadMissing([
                                'roleAssignments.user',
                                'assignedUser',
                                'organization',
                                'contact',
                                'lastNote.user',
                            ]);

                            return [];
                        },
                    ],
                    'updates' => [
                        'label' => 'به‌روزرسانی‌ها',
                        'view' => 'sales.opportunities.tabs.updates',
                        'data' => function (Opportunity $opportunity) {
                            $activities = Activity::where('subject_type', Opportunity::class)
                                ->where('subject_id', $opportunity->id)
                                ->where(function ($query) {
                                    $query->whereIn('event', [
                                        'created',
                                        'updated',
                                        'proforma_created',
                                        'document_voided',
                                        'document_unvoided',
                                        'contact_attached',
                                        'contact_detached',
                                    ])->orWhereNull('event');
                                })
                                ->latest()
                                ->get();

                            return ['activities' => $activities];
                        },
                    ],
                    'notes' => [
                        'label' => 'یادداشت‌ها',
                        'view' => 'sales.opportunities.tabs.notes',
                        'data' => function () {
                            return ['allUsers' => User::whereNotNull('username')->get()];
                        },
                    ],
                    'contacts' => [
                        'label' => 'مخاطبین',
                        'view' => 'sales.opportunities.tabs.contacts',
                        'data' => function (Opportunity $opportunity) {
                            $opportunity->loadMissing(['contact.organization', 'contacts.organization']);
                            $contacts = $opportunity->contacts;
                            if ($opportunity->contact && !$contacts->contains('id', $opportunity->contact->id)) {
                                $contacts = $contacts->prepend($opportunity->contact);
                            }

                            $allContacts = Contact::visibleFor(auth()->user(), 'contacts')
                                ->select('id', 'first_name', 'last_name', 'mobile')
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get();

                            return [
                                'contacts' => $contacts,
                                'allContacts' => $allContacts,
                            ];
                        },
                    ],
                    'proformas' => [
                        'label' => 'پیش‌فاکتورها',
                        'view' => 'sales.opportunities.tabs.proformas',
                        'data' => function (Opportunity $opportunity) {
                            $opportunity->load('proformas');
                            return [];
                        },
                    ],
                    'files' => [
                        'label' => 'فایل‌ها',
                        'view' => 'sales.opportunities.tabs.documents',
                        'data' => function (Opportunity $opportunity) {
                            $opportunity->load('documents');
                            return [];
                        },
                    ],
                    'calls' => [
                        'label' => 'تماس‌ها',
                        'view_mode' => 'html',
                        'blocks' => [
                            [
                                'type' => 'html',
                                'html' => '<div class="text-sm text-gray-500">تب تماس‌ها هنوز آماده نشده است.</div>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
