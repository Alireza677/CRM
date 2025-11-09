@php use Morilog\Jalali\Jalalian; @endphp

<div class="font-vazirmatn" lang="fa" dir="rtl">
    @php
        // Common labels
        $settlementMap = [
            'cash'   => 'نقد',
            'credit' => 'نسیه',
            'cheque' => 'چک',
            'operational_expense' => 'هزینه جاری',
        ];
        $settlementLabel = $settlementMap[$purchaseOrder->settlement_type ?? ''] ?? '—';

        $usageMap = [
            'inventory' => 'تکمیل موجودی انبار',
            'project'   => 'تکمیل پروژه',
            'both'      => 'هر دو',
        ];
        $usageLabel = $usageMap[$purchaseOrder->usage_type ?? ''] ?? '—';

        $statusMap = [
            'created'              => ['ایجاد شده', 'bg-blue-100 text-blue-800'],
            'supervisor_approval'  => ['تأیید سرپرست کارخانه', 'bg-amber-100 text-amber-800'],
            'manager_approval'     => ['تأیید مدیر کل', 'bg-yellow-100 text-yellow-800'],
            'accounting_approval'  => ['تأیید حسابداری / پرداخت', 'bg-teal-100 text-teal-800'],
            'purchased'            => ['خرید انجام شده', 'bg-green-100 text-green-800'],
            'purchasing'           => ['در حال خرید', 'bg-indigo-100 text-indigo-800'],
            'warehouse_delivered'  => ['تحویل انبار', 'bg-green-100 text-green-800'],
            'rejected'             => ['رد شده', 'bg-red-100 text-red-800'],
        ];
        [$statusText, $statusBadge] = $statusMap[$purchaseOrder->status] ?? ['نامشخص','bg-gray-100 text-gray-800'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Card 1: اطلاعات اصلی سفارش -->
        <div class="lg:col-span-4 rounded-2xl border border-green-200 bg-green-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-green-800 mb-3">اطلاعات اصلی سفارش</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">شماره سفارش</span>
                        <span class="font-medium text-gray-900">{{ $purchaseOrder->po_number ?? ('#'.$purchaseOrder->id) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">عنوان سفارش</span>
                        <span class="font-medium text-gray-900">{{ $purchaseOrder->subject ?: '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">نوع خرید</span>
                        <span class="font-medium text-gray-900">{{ $purchaseOrder->purchase_type === 'unofficial' ? 'غیررسمی' : 'رسمی' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">نوع تسویه حساب</span>
                        <span class="font-medium text-gray-900">{{ $settlementLabel }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: مشخصات اجرایی -->
        <div class="lg:col-span-4 rounded-2xl border border-sky-200 bg-sky-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-sky-800 mb-3">مشخصات اجرایی</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-600">موارد استفاده</span>
                        <span class="font-medium text-gray-900 text-left">
                            {{ $usageLabel }}
                            @if(in_array($purchaseOrder->usage_type ?? null, ['project','both'], true) && !empty($purchaseOrder->project_name))
                                <span class="text-gray-500">— پروژه: {{ $purchaseOrder->project_name }}</span>
                            @elseif(($purchaseOrder->usage_type ?? null) === 'operational_expense')
                                @php
                                    $opMap = [
                                        'commission'       => 'هزینه کمیسیون',
                                        'installation'     => 'هزینه نصب',
                                        'shipping'         => 'هزینه حمل',
                                        'workshop_running' => 'هزینه جاری کارگاه',
                                    ];
                                    $opLabel = $opMap[$purchaseOrder->operational_expense_type ?? ''] ?? null;
                                @endphp
                                @if($opLabel)
                                    <span class="text-gray-500">— نوع: {{ $opLabel }}</span>
                                @endif
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تأمین‌کننده</span>
                        <span class="font-medium text-gray-900">{{ optional($purchaseOrder->supplier)->name ?: '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ارجاع به</span>
                        <span class="font-medium text-gray-900">{{ optional($purchaseOrder->assignedUser)->name ?: '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">درخواست‌کننده</span>
                        <span class="font-medium text-gray-900">{{ optional($purchaseOrder->requestedByUser)->name ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: زمان‌ها و وضعیت -->
        <div class="lg:col-span-4 rounded-2xl border border-violet-200 bg-violet-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-violet-800 mb-3">زمان‌ها و وضعیت</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ درخواست</span>
                        <span class="font-medium text-gray-900">{{ $purchaseOrder->request_date ? Jalalian::fromCarbon($purchaseOrder->request_date)->format('Y/m/d') : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ خرید</span>
                        <span class="font-medium text-gray-900">{{ $purchaseOrder->purchase_date ? Jalalian::fromCarbon($purchaseOrder->purchase_date)->format('Y/m/d') : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ نیاز</span>
                        <span class="font-medium text-gray-900">{{ $purchaseOrder->needed_by_date ? Jalalian::fromCarbon($purchaseOrder->needed_by_date)->format('Y/m/d') : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">وضعیت</span>
                        <span class="font-medium">
                            <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $statusBadge }}">{{ $statusText }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: وضعیت مالی -->
    <div class="mt-4 grid grid-cols-1">
        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-amber-800 mb-3">وضعیت مالی</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-3 rounded-lg bg-white/60 border border-amber-100">
                        <div class="text-xs text-gray-500">جمع اقلام</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($purchaseOrder->total_amount, 0) }} ریال</div>
                    </div>
                    <div class="p-3 rounded-lg bg-white/60 border border-amber-100">
                        <div class="text-xs text-gray-500">پیش‌پرداخت</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($purchaseOrder->previously_paid_amount ?? 0, 0) }} ریال</div>
                    </div>
                    <div class="p-3 rounded-lg bg-white/60 border border-amber-100">
                        <div class="text-xs text-gray-500">مانده قابل پرداخت</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($purchaseOrder->remaining_payable_amount ?? 0, 0) }} ریال</div>
                    </div>
                </div>

                @if(($purchaseOrder->vat_percent ?? 0) > 0)
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                            ارزش افزوده {{ rtrim(rtrim(number_format($purchaseOrder->vat_percent, 2), '0'), '.') }}%
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                            مبلغ: {{ number_format($purchaseOrder->vat_amount ?? 0, 0) }} ریال
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                            جمع با مالیات: {{ number_format($purchaseOrder->total_with_vat ?? ($purchaseOrder->total_amount + ($purchaseOrder->vat_amount ?? 0)), 0) }} ریال
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($purchaseOrder->description))
        <div class="mt-4 rounded-xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-base font-semibold mb-2 text-gray-800">توضیحات و اطلاعات حساب بانکی</h2>
            <div class="whitespace-pre-line text-gray-800">{{ $purchaseOrder->description }}</div>
        </div>
    @endif
</div>

<div class="bg-white rounded-lg shadow-md p-6 mt-4 font-vazirmatn" lang="fa" dir="rtl">
    @php \App\Helpers\DateHelper::class; @endphp
    @php
        $createdAtFa = \App\Helpers\DateHelper::toJalali($purchaseOrder->created_at, 'H:i Y/m/d');
        // Ensure workflow settings are available for substitute names
        $wf = $wf ?? ($poSettings ?? \App\Models\PurchaseOrderWorkflowSetting::first());
        $a1 = $purchaseOrder->approvals()->with('approver')->where('status','approved')->where('step',1)->orderByDesc('approved_at')->first();
        $a2 = $purchaseOrder->approvals()->with('approver')->where('status','approved')->where('step',2)->orderByDesc('approved_at')->first();
        $a3 = $purchaseOrder->approvals()->with('approver')->where('status','approved')->where('step',3)->orderByDesc('approved_at')->first();
        $a1AtFa = $a1?->approved_at ? \App\Helpers\DateHelper::toJalali($a1->approved_at, 'H:i Y/m/d') : null;
        $a2AtFa = $a2?->approved_at ? \App\Helpers\DateHelper::toJalali($a2->approved_at, 'H:i Y/m/d') : null;
        $a3AtFa = $a3?->approved_at ? \App\Helpers\DateHelper::toJalali($a3->approved_at, 'H:i Y/m/d') : null;
        $pendingLabel = match($purchaseOrder->status) {
            'supervisor_approval' => 'در انتظار تأیید سرپرست کارخانه',
            'manager_approval'    => 'در انتظار تأیید مدیر کل',
            'accounting_approval' => 'در انتظار تأیید حسابداری / پرداخت',
            default               => null,
        };
    @endphp

    @php
        $currentUserId = (int) auth()->id();
        $isCreator     = $currentUserId === (int) ($purchaseOrder->requested_by ?? 0);

        // Determine whether we are at an approval stage
        $status = $purchaseOrder->status ?? 'created';
        $approvalStages = ['created','supervisor_approval','manager_approval','accounting_approval'];
        $inApprovalStage = in_array($status, $approvalStages, true);

        // Determine eligibility solely by main/sub approver for the stage (ignore assigned_to)
        $showDecisionButtons = false;
        if ($inApprovalStage) {
            $wf = $poSettings ?? \App\Models\PurchaseOrderWorkflowSetting::first();
            $mainId = null; $subId = null;
            if ($status === 'accounting_approval') {
                $mainId = optional($wf)->accounting_user_id;
                $subId  = optional($wf)->accounting_approver_substitute_id;
            } elseif ($status === 'manager_approval') {
                $mainId = optional($wf)->second_approver_id;
                $subId  = optional($wf)->second_approver_substitute_id;
            } else { // created or supervisor_approval
                $mainId = optional($wf)->first_approver_id;
                $subId  = optional($wf)->first_approver_substitute_id;
            }
            $showDecisionButtons = ($currentUserId === (int) ($mainId ?? 0)) || ($currentUserId === (int) ($subId ?? 0));
        }
    @endphp

    <h3 class="text-md font-semibold mb-3">تایم‌لاین تأییدات</h3>
    <div class="space-y-2 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-600">ثبت سفارش</span>
            <span class="font-medium">{{ $createdAtFa ?: '—' }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">تأیید سرپرست کارخانه</span>
            <span class="font-medium">
                {{ $a1AtFa ?: '-' }} @if($a1?->approver) - {{ $a1->approver->name }} @php $__sub = optional(($wf ?? ($poSettings ?? \App\Models\PurchaseOrderWorkflowSetting::first()))?->firstApproverSubstitute)->name ?? null; @endphp @if($__sub) ({{ $__sub }}) @endif @endif
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">تأیید مدیر کل</span>
            <span class="font-medium">
                {{ $a2AtFa ?: '-' }} @if($a2?->approver) - {{ $a2->approver->name }} @php $__sub = optional(($wf ?? ($poSettings ?? \App\Models\PurchaseOrderWorkflowSetting::first()))?->secondApproverSubstitute)->name ?? null; @endphp @if($__sub) ({{ $__sub }}) @endif @endif
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">تأیید حسابداری / پرداخت</span>
            <span class="font-medium">
                {{ $a3AtFa ?: '-' }} @if($a3?->approver) - {{ $a3->approver->name }} @php $__sub = optional(($wf ?? ($poSettings ?? \App\Models\PurchaseOrderWorkflowSetting::first()))?->accountingApproverSubstitute)->name ?? null; @endphp @if($__sub) ({{ $__sub }}) @endif @endif
            </span>
        </div>
        @if($pendingLabel && !($a3AtFa))
            <div class="pt-2 text-gray-500">{{ $pendingLabel }}</div>
        @endif
    </div>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mt-4 font-vazirmatn" lang="fa" dir="rtl">
    <h3 class="text-md font-semibold mb-2">تصمیم‌گیری</h3>
    @php
        $stageOrder = [
            'created' => 0,
            'supervisor_approval' => 1,
            'manager_approval' => 2,
            'accounting_approval' => 3,
            'purchased' => 4,
        ];
        $currentIndex = $stageOrder[$purchaseOrder->status] ?? 0;

        // اجازه‌ی به‌روزرسانی در مراحل تأیید فقط برای تأییدکننده‌ی همان مرحله
        $canUpdate = true;
        if (in_array($purchaseOrder->status, ['supervisor_approval','manager_approval','accounting_approval'], true)) {
            $expectedId = null;
            if ($purchaseOrder->status === 'supervisor_approval') {
                $expectedId = optional($poSettings ?? null)->first_approver_id;
            } elseif ($purchaseOrder->status === 'manager_approval') {
                $expectedId = optional($poSettings ?? null)->second_approver_id;
            } elseif ($purchaseOrder->status === 'accounting_approval') {
                $expectedId = optional($poSettings ?? null)->accounting_user_id;
            }
            $canUpdate = (int) auth()->id() === (int) $expectedId;
        }

        // Ensure substitutes and assigned_to effective approvers can act
        $wf = $wf ?? ($poSettings ?? \App\Models\PurchaseOrderWorkflowSetting::first());
        if (in_array($purchaseOrder->status, ['supervisor_approval','manager_approval','accounting_approval'], true)) {
            $assignedId = (int) ($purchaseOrder->assigned_to ?? 0);
            if ($assignedId > 0) {
                $canUpdate = ((int) auth()->id() === $assignedId);
            } else {
                $mainId = null; $subId = null;
                if ($purchaseOrder->status === 'supervisor_approval' || $purchaseOrder->status === 'created') {
                    $mainId = optional($wf)->first_approver_id;
                    $subId  = optional($wf)->first_approver_substitute_id;
                } elseif ($purchaseOrder->status === 'manager_approval') {
                    $mainId = optional($wf)->second_approver_id;
                    $subId  = optional($wf)->second_approver_substitute_id;
                } elseif ($purchaseOrder->status === 'accounting_approval') {
                    $mainId = optional($wf)->accounting_user_id;
                    $subId  = optional($wf)->accounting_approver_substitute_id;
                }
                $effectiveId = (int) ($mainId ?? 0);
                if (empty($effectiveId) && !empty($subId)) {
                    $effectiveId = (int) $subId;
                } else {
                    try {
                        $user = \App\Models\User::find($mainId);
                        $onLeave = (bool) ($user->is_on_leave ?? false);
                        if ($onLeave && !empty($subId)) { $effectiveId = (int) $subId; }
                    } catch (\Throwable $e) {}
                }
                $canUpdate = ((int) auth()->id() === $effectiveId);
            }
        }
    @endphp

    @if($showDecisionButtons)
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('inventory.purchase-orders.approve', $purchaseOrder) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded text-white bg-green-600 hover:bg-green-700">تأیید سفارش</button>
            </form>
            <button type="button" class="px-4 py-2 rounded text-white bg-red-600 hover:bg-red-700" onclick="document.getElementById('rejectModal')?.classList.remove('hidden'); document.getElementById('rejectModal')?.classList.add('flex');">رد سفارش</button>
        </div>
        <p class="text-xs text-gray-500 mt-2">فقط تأییدکنندهٔ مجازِ این مرحله می‌تواند تصمیم بگیرد.</p>
    @elseif($purchaseOrder->status === 'purchasing' && $isCreator)
        <form method="POST" action="{{ route('inventory.purchase-orders.deliverToWarehouse', $purchaseOrder) }}">
            @csrf
            <button type="submit" class="px-4 py-2 rounded text-white bg-indigo-600 hover:bg-indigo-700">تحویل به انباردار</button>
        </form>
        <p class="text-xs text-gray-500 mt-2">پس از تحویل اقلام به انبار، این دکمه را بزنید تا وضعیت به «تحویل انبار» تغییر کند.</p>
    @else
        {{-- در این مرحله دکمه‌ای نمایش داده نمی‌شود --}}
    @endif

    <!-- Modal: Reject Reason -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h4 class="font-semibold text-gray-800">دلیل رد سفارش خرید</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('rejectModal')?.classList.add('hidden'); document.getElementById('rejectModal')?.classList.remove('flex');">×</button>
            </div>
            <form method="POST" action="{{ route('inventory.purchase-orders.reject', $purchaseOrder) }}">
                @csrf
                <div class="p-4 space-y-3">
                    <label class="block text-sm text-gray-700 mb-1">لطفاً دلیل رد سفارش را وارد کنید</label>
                    <textarea name="reject_reason" required maxlength="2000" class="w-full border rounded p-2 min-h-[120px]" placeholder="مثلاً: قیمت نامناسب، موارد ناقص، ..."></textarea>
                </div>
                <div class="px-4 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                    <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-900 hover:bg-gray-300" onclick="document.getElementById('rejectModal')?.classList.add('hidden'); document.getElementById('rejectModal')?.classList.remove('flex');">انصراف</button>
                    <button type="submit" class="px-4 py-2 rounded text-white bg-red-600 hover:bg-red-700">ثبت رد سفارش</button>
                </div>
            </form>
        </div>
    </div>
</div>
