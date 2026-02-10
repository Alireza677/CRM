<?php

namespace App\Crud\Schemas;

use App\Models\Proforma;
use App\Models\User;
use App\Helpers\FormOptionsHelper;
use App\Helpers\DateHelper;
use Spatie\Activitylog\Models\Activity;

class ProformaSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'proformas',
            'title' => 'پیش‌فاکتورها',
            'model' => Proforma::class,
            'routes' => [
                'index' => 'sales.proformas.index',
                'create' => 'sales.proformas.create',
                'show' => 'sales.proformas.show',
                'edit' => 'sales.proformas.edit',
                'destroy' => 'sales.proformas.destroy',
                'bulkDestroy' => 'sales.proformas.bulk-destroy',
                'import' => 'sales.proformas.import.form',
            ],
            'per_page' => 25,
            'per_page_options' => [25, 50, 100],
            'query' => function ($query, $request) {
                $user = $request->user();
                if ($user) {
                    $query->visibleFor($user, 'proformas');
                }

                $query->with(['organization', 'contact', 'opportunity.organization', 'opportunity.contact', 'assignedTo'])
                    ->orderByDesc('proforma_date')
                    ->orderByDesc('created_at');
            },
            'columns' => [
                [
                    'key' => 'proforma_number',
                    'label' => 'شماره',
                    'type' => 'text',
                    'sortable' => true,
                    'width' => 'w-28',
                ],
                [
                    'key' => 'subject',
                    'label' => 'عنوان',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'organization.name',
                    'label' => 'سازمان',
                    'type' => 'text',
                    'format' => function ($row) {
                        return $row->opportunity?->organization?->name ?? '—';
                    },
                ],
                [
                    'key' => 'contact.full_name',
                    'label' => 'مخاطب',
                    'type' => 'text',
                    'format' => function ($row) {
                        return $row->opportunity?->contact?->full_name ?? '—';
                    },
                ],
                [
                    'key' => 'opportunity.name',
                    'label' => 'فرصت',
                    'type' => 'html',
                    'format' => function ($row) {
                        $opportunity = $row->opportunity ?? null;
                        if (!$opportunity) {
                            return '—';
                        }

                        $name = $opportunity->name ?: ('#' . $opportunity->id);
                        $url = route('sales.opportunities.show', $opportunity);

                        return '<a href="' . $url . '" class="text-indigo-600 hover:underline">' . e($name) . '</a>';
                    },
                ],
                [
                    'key' => 'total_amount',
                    'label' => 'مبلغ کل',
                    'type' => 'text',
                ],
                [
                    'key' => 'approval_stage',
                    'label' => 'وضعیت',
                    'type' => 'badge',
                    'sortable' => true,
                    'format' => function ($row) {
                        $stage = $row->approval_stage ?? $row->proforma_stage;
                        return FormOptionsHelper::proformaStages()[$stage] ?? $stage ?? '—';
                    },
                    'badges' => [
                        'send_for_approval' => 'bg-amber-50 text-amber-700',
                        'awaiting_second_approval' => 'bg-indigo-50 text-indigo-700',
                        'approved' => 'bg-emerald-50 text-emerald-700',
                        'rejected' => 'bg-rose-50 text-rose-700',
                        'draft' => 'bg-gray-100 text-gray-700',
                        'issued_invoice' => 'bg-slate-100 text-slate-700',
                        'converted' => 'bg-emerald-50 text-emerald-700',
                    ],
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
                    'key' => 'proforma_number',
                    'type' => 'text',
                    'placeholder' => 'شماره',
                ],
                [
                    'key' => 'subject',
                    'type' => 'text',
                    'placeholder' => 'عنوان',
                ],
                [
                    'key' => 'organization',
                    'column' => 'organization.name',
                    'type' => 'text',
                    'columns' => ['organization_name', 'organization.name'],
                    'placeholder' => 'سازمان',
                ],
                [
                    'key' => 'contact',
                    'column' => 'contact.full_name',
                    'type' => 'text',
                    'columns' => ['contact_name', 'contact.first_name', 'contact.last_name', 'contact.company', 'contact.mobile'],
                    'placeholder' => 'مخاطب',
                ],
                [
                    'key' => 'opportunity',
                    'column' => 'opportunity.name',
                    'type' => 'text',
                    'columns' => ['opportunity.name'],
                    'placeholder' => 'فرصت',
                ],
                [
                    'key' => 'proforma_stage',
                    'column' => 'approval_stage',
                    'type' => 'select',
                    'options' => [FormOptionsHelper::class, 'proformaStages'],
                    'placeholder' => 'وضعیت',
                ],
                [
                    'key' => 'assigned_to',
                    'column' => 'assignedTo.name',
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
                'load' => ['organization', 'contact', 'opportunity', 'assignedTo', 'items', 'notes', 'approvals'],
                'withCount' => ['notes', 'activities', 'items', 'approvals'],
                'tabs' => [
                    'info' => [
                        'label' => 'اطلاعات پایه',
                        'view' => 'sales.proformas.tabs.info',
                        'data' => function (Proforma $m) {
                            $items = $m->relationLoaded('items') ? $m->items : $m->items()->get();
                            $subtotal = 0.0;
                            foreach ($items as $item) {
                                $subtotal += (float) ($item->total_price ?? 0);
                            }

                            $discType = $m->global_discount_type ?? null;
                            $discVal = (float) ($m->global_discount_value ?? 0);
                            $taxType = $m->global_tax_type ?? null;
                            $taxVal = (float) ($m->global_tax_value ?? 0);

                            if (isset($m->global_discount_amount)) {
                                $discount = (float) $m->global_discount_amount;
                            } else {
                                $discount = $discType === 'percentage'
                                    ? ($subtotal * $discVal) / 100
                                    : ($discType === 'fixed' ? $discVal : 0);
                            }

                            $discount = min($discount, $subtotal);
                            $afterDiscount = $subtotal - $discount;

                            if (isset($m->global_tax_amount)) {
                                $tax = (float) $m->global_tax_amount;
                            } else {
                                $tax = $taxType === 'percentage'
                                    ? ($afterDiscount * $taxVal) / 100
                                    : ($taxType === 'fixed' ? $taxVal : 0);
                            }

                            $tax = max($tax, 0);
                            $grand = isset($m->total_amount)
                                ? (float) $m->total_amount
                                : ($afterDiscount + $tax);

                            $stage = $m->approval_stage ?? $m->proforma_stage;
                            $stageLabel = FormOptionsHelper::proformaStages()[$stage] ?? $stage ?? '—';
                            $pending = $m->approvals()
                                ->with('approver')
                                ->where('status', 'pending')
                                ->first();

                            return [
                                'items' => $items,
                                'subtotal' => $subtotal,
                                'discount' => $discount,
                                'tax' => $tax,
                                'grand' => $grand,
                                'shamsiDate' => DateHelper::toJalali($m->proforma_date, 'Y/m/d'),
                                'stageLabel' => $stageLabel,
                                'pending' => $pending,
                            ];
                        },
                    ],
                    'items' => [
                        'label' => 'آیتم‌ها',
                        'view' => 'sales.proformas.tabs.items',
                        'data' => function (Proforma $m) {
                            $items = $m->relationLoaded('items') ? $m->items : $m->items()->get();
                            $subtotal = 0.0;
                            foreach ($items as $item) {
                                $subtotal += (float) ($item->total_price ?? 0);
                            }

                            $discType = $m->global_discount_type ?? null;
                            $discVal = (float) ($m->global_discount_value ?? 0);
                            $taxType = $m->global_tax_type ?? null;
                            $taxVal = (float) ($m->global_tax_value ?? 0);

                            if (isset($m->global_discount_amount)) {
                                $discount = (float) $m->global_discount_amount;
                            } else {
                                $discount = $discType === 'percentage'
                                    ? ($subtotal * $discVal) / 100
                                    : ($discType === 'fixed' ? $discVal : 0);
                            }

                            $discount = min($discount, $subtotal);
                            $afterDiscount = $subtotal - $discount;

                            if (isset($m->global_tax_amount)) {
                                $tax = (float) $m->global_tax_amount;
                            } else {
                                $tax = $taxType === 'percentage'
                                    ? ($afterDiscount * $taxVal) / 100
                                    : ($taxType === 'fixed' ? $taxVal : 0);
                            }

                            $tax = max($tax, 0);
                            $grand = isset($m->total_amount)
                                ? (float) $m->total_amount
                                : ($afterDiscount + $tax);

                            return [
                                'items' => $items,
                                'subtotal' => $subtotal,
                                'discount' => $discount,
                                'tax' => $tax,
                                'grand' => $grand,
                            ];
                        },
                    ],
                    'updates' => [
                        'label' => 'به‌روزرسانی‌ها',
                        'view' => 'sales.proformas.tabs.updates',
                        'data' => function (Proforma $m) {
                            $updates = Activity::query()
                                ->where('subject_type', Proforma::class)
                                ->where('subject_id', $m->id)
                                ->latest()
                                ->get();

                            return [
                                'updates' => $updates,
                            ];
                        },
                    ],
                    'notes' => [
                        'label' => 'یادداشت‌ها',
                        'view' => 'sales.proformas.tabs.notes',
                        'data' => function () {
                            $allUsers = User::query()
                                ->select(['id', 'name', 'username'])
                                ->whereNotNull('username')
                                ->orderBy('name')
                                ->get();

                            return [
                                'allUsers' => $allUsers,
                            ];
                        },
                    ],
                    'approvals' => [
                        'label' => 'تاییدیه‌ها',
                        'view' => 'sales.proformas.tabs.approvals',
                        'data' => function (Proforma $proforma) {
                            $formatDate = static function ($date) {
                                return $date ? DateHelper::toJalali($date, 'H:i Y/m/d') : '—';
                            };

                            $approvals = $proforma->relationLoaded('approvals')
                                ? $proforma->approvals->loadMissing('approver', 'approvedBy', 'decidedBy')
                                : $proforma->approvals()->with(['approver', 'approvedBy', 'decidedBy'])->get();

                            $approvals = $approvals->sortBy(function ($approval) {
                                return sprintf('%02d-%010d', (int)($approval->step ?? 99), (int)($approval->id ?? 0));
                            });

                            $buildStep = static function (int $step) use ($approvals, $formatDate) {
                                $byStep   = $approvals->where('step', $step);
                                $approved = $byStep->firstWhere('status', 'approved');
                                $rejected = $byStep->firstWhere('status', 'rejected');
                                $pending  = $byStep->firstWhere('status', 'pending');

                                $statusClass = 'bg-amber-50 text-amber-800';
                                $statusLabel = 'در انتظار تأیید';
                                $dateDisplay = '—';
                                $approvedAt  = null;
                                $actor       = $approved ?? $rejected ?? $pending;
                                $primaryId   = $actor?->user_id ? (int) $actor->user_id : null;
                                $decidedBy   = null;
                                $deciderId   = null;
                                $mainName    = optional($actor?->approver)->name;
                                $subName     = null;
                                $mainApproved = false;
                                $subApproved  = false;

                                if ($actor) {
                                    if (in_array($actor->status, ['approved', 'rejected'], true)) {
                                        $decidedBy = $actor->decidedBy ?? $actor->approvedBy ?? null;
                                        $deciderId = $decidedBy?->id ?? null;
                                    }

                                    if ($actor->status === 'approved') {
                                        if ($deciderId && $primaryId && $deciderId === $primaryId) {
                                            $mainApproved = true;
                                        } elseif ($deciderId && $primaryId && $deciderId !== $primaryId) {
                                            $subApproved = true;
                                        }
                                    }

                                    if ($deciderId && $primaryId && $deciderId !== $primaryId) {
                                        $subName = $decidedBy?->name;
                                    }
                                }

                                if ($rejected) {
                                    $statusClass = 'bg-red-50 text-red-800';
                                    $statusLabel = 'رد شده';
                                    $approvedAt  = $rejected->approved_at ?? $rejected->created_at;
                                } elseif ($approved) {
                                    $statusClass = 'bg-green-50 text-green-800';
                                    $statusLabel = 'تأیید شده';
                                    $approvedAt  = $approved->approved_at ?? $approved->created_at;
                                }

                                if ($approvedAt) {
                                    $dateDisplay = $formatDate($approvedAt);
                                }

                                $decidedClass = null;
                                if ($approved) {
                                    $decidedClass = 'bg-green-100';
                                } elseif ($rejected) {
                                    $decidedClass = 'bg-red-100 text-red-800';
                                }

                                $mainCellClass = '';
                                $subCellClass  = '';
                                if ($decidedClass && $deciderId) {
                                    if ($primaryId && $deciderId === $primaryId) {
                                        $mainCellClass = $decidedClass;
                                    } elseif ($primaryId && $deciderId !== $primaryId) {
                                        $subCellClass = $decidedClass;
                                    }
                                }

                                return [
                                    'status_class'      => $statusClass,
                                    'status_label'      => $statusLabel,
                                    'date_display'      => $dateDisplay,
                                    'main_cell_class'   => $mainCellClass,
                                    'sub_cell_class'    => $subCellClass,
                                    'main_name' => $mainName ?: '—',
                                    'sub_name'  => $subName ?: '—',
                                    'main_approved'     => $mainApproved,
                                    'sub_approved'      => $subApproved,
                                    'approved_at'       => $approvedAt,
                                    'approved_at_fa'    => $approvedAt ? $formatDate($approvedAt) : null,
                                    'pending_approver'  => optional($pending?->approver)->name,
                                ];
                            };

                            $step1 = $buildStep(1);
                            $step2 = $buildStep(2);
                            $step3 = $buildStep(3);

                            $lastApprovedAt = collect([$step3['approved_at'], $step2['approved_at'], $step1['approved_at']])
                                ->filter()
                                ->sortDesc()
                                ->first();

                            $durationText = null;
                            try {
                                if ($proforma->created_at && $lastApprovedAt) {
                                    $minutes = $proforma->created_at->diffInMinutes($lastApprovedAt);
                                    $days    = intdiv($minutes, 60 * 24);
                                    $hours   = intdiv($minutes % (60 * 24), 60);
                                    $mins    = $minutes % 60;

                                    $parts = [];
                                    if ($days) {
                                        $parts[] = $days . ' روز';
                                    }
                                    if ($hours) {
                                        $parts[] = $hours . ' ساعت';
                                    }
                                    if ($mins && $days === 0) {
                                        $parts[] = $mins . ' دقیقه';
                                    }

                                    $durationText = $parts ? implode(' و ', $parts) : null;
                                }
                            } catch (\Throwable $e) {
                                $durationText = null;
                            }

                            $currentUserId       = (int) auth()->id();
                            $activePending       = $approvals->where('status', 'pending')->first();
                            $emergencyApproverId = (int) optional($proforma->automationRule()->select('id', 'emergency_approver_id')->first())->emergency_approver_id;
                            $showDecisionButtons = $activePending
                                && (
                                    (int) $activePending->user_id === $currentUserId
                                    || ($emergencyApproverId && $emergencyApproverId === $currentUserId)
                                );
                            $createdAtFa         = $formatDate($proforma->created_at);
                            $pendingApproverName = $activePending?->approver?->name
                                ?? $step1['pending_approver']
                                ?? $step2['pending_approver']
                                ?? $step3['pending_approver']
                                ?? null;

                            return [
                                'createdAtFa'                      => $createdAtFa,
                                'durationText'                     => $durationText,
                                'firstApprovedAtFa'                => $step1['approved_at_fa'],
                                'secondApprovedAtFa'               => $step2['approved_at_fa'],
                                'a1StatusClass'                    => $step1['status_class'],
                                'a1StatusLabel'                    => $step1['status_label'],
                                'a1DateDisplay'                    => $step1['date_display'],
                                'firstApproverName'                => $step1['main_name'],
                                'firstApproverSubstituteName'      => $step1['sub_name'],
                                'firstApproverMainApproved'        => $step1['main_approved'],
                                'firstApproverSubstituteApproved'  => $step1['sub_approved'],
                                'firstMainCellClass'               => $step1['main_cell_class'],
                                'firstSubCellClass'                => $step1['sub_cell_class'],
                                'a2StatusClass'                    => $step2['status_class'],
                                'a2StatusLabel'                    => $step2['status_label'],
                                'a2DateDisplay'                    => $step2['date_display'],
                                'secondApproverName'               => $step2['main_name'],
                                'secondApproverSubstituteName'     => $step2['sub_name'],
                                'secondApproverMainApproved'       => $step2['main_approved'],
                                'secondApproverSubstituteApproved' => $step2['sub_approved'],
                                'secondMainCellClass'              => $step2['main_cell_class'],
                                'secondSubCellClass'               => $step2['sub_cell_class'],
                                'a3StatusClass'                    => $step3['status_class'],
                                'a3StatusLabel'                    => $step3['status_label'],
                                'a3DateDisplay'                    => $step3['date_display'],
                                'accountingApproverName'           => $step3['main_name'],
                                'accountingApproverSubstituteName' => $step3['sub_name'],
                                'accountingApproverMainApproved'   => $step3['main_approved'],
                                'accountingApproverSubstituteApproved' => $step3['sub_approved'],
                                'accountingMainCellClass'          => $step3['main_cell_class'],
                                'accountingSubCellClass'           => $step3['sub_cell_class'],
                                'showDecisionButtons'              => $showDecisionButtons,
                                'pendingApproverName'              => $pendingApproverName,
                            ];
                        },
                    ],
                    'documents' => [
                        'label' => 'اسناد',
                        'view' => 'sales.proformas.tabs.documents',
                        'data' => function (Proforma $m) {
                            $documents = collect();
                            $opportunity = $m->opportunity;
                            if ($opportunity) {
                                $documents = $opportunity->documents()
                                    ->latest()
                                    ->get();
                            }

                            return [
                                'documents' => $documents,
                            ];
                        },
                    ],
                ],
            ],
        ];
    }
}
