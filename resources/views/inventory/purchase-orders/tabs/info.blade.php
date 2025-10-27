@php use Morilog\Jalali\Jalalian; @endphp

<div class="bg-white rounded-lg shadow-md p-6 font-vazirmatn" lang="fa" dir="rtl">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h2 class="text-lg font-semibold mb-4">اطلاعات اصلی</h2>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">شماره سفارش</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->po_number ?? ('#'.$purchaseOrder->id) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">عنوان سفارش</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->subject ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">نوع خرید</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $purchaseOrder->purchase_type === 'unofficial' ? 'غیررسمی' : 'رسمی' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">نوع تسویه حساب</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @php
                            $settlementMap = [
                                'cash'   => 'نقد',
                                'credit' => 'نسیه',
                                'cheque' => 'چک',
                            ];
                            $settlementLabel = $settlementMap[$purchaseOrder->settlement_type ?? ''] ?? '—';
                        @endphp
                        {{ $settlementLabel }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">مورد استفاده</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @php
                            $usageMap = [
                                'inventory' => 'تکمیل موجودی انبار',
                                'project'   => 'تکمیل پروژه',
                                'both'      => 'هر دو',
                            ];
                            $usageLabel = $usageMap[$purchaseOrder->usage_type ?? ''] ?? '—';
                        @endphp
                        {{ $usageLabel }}
                        @if(in_array($purchaseOrder->usage_type ?? null, ['project','both'], true) && !empty($purchaseOrder->project_name))
                            <span class="text-gray-500">— پروژه: {{ $purchaseOrder->project_name }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">تأمین‌کننده</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($purchaseOrder->supplier)->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">ارجاع به</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($purchaseOrder->assignedUser)->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">درخواست‌کننده</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($purchaseOrder->requestedByUser)->name ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div>
            <h2 class="text-lg font-semibold mb-4">زمان‌ها و وضعیت</h2>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">تاریخ درخواست</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $purchaseOrder->request_date ? Jalalian::fromCarbon($purchaseOrder->request_date)->format('Y/m/d') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">تاریخ خرید</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $purchaseOrder->purchase_date ? Jalalian::fromCarbon($purchaseOrder->purchase_date)->format('Y/m/d') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">تاریخ نیاز</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $purchaseOrder->needed_by_date ? Jalalian::fromCarbon($purchaseOrder->needed_by_date)->format('Y/m/d') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @php
                            $map = [
                                'created'              => ['ایجاد شده', 'bg-blue-100 text-blue-800'],
                                'supervisor_approval'  => ['تأیید سرپرست کارخانه', 'bg-amber-100 text-amber-800'],
                                'manager_approval'     => ['تأیید مدیر کل', 'bg-yellow-100 text-yellow-800'],
                                'accounting_approval'  => ['تأیید حسابداری / پرداخت', 'bg-teal-100 text-teal-800'],
                                'purchased'            => ['خرید انجام شده', 'bg-green-100 text-green-800'],
                                'purchasing'           => ['در حال خرید', 'bg-indigo-100 text-indigo-800'],
                                'warehouse_delivered'  => ['تحویل انبار', 'bg-green-100 text-green-800'],
                                'rejected'             => ['رد شده', 'bg-red-100 text-red-800'],
                            ];
                            [$statusText, $badge] = $map[$purchaseOrder->status] ?? ['نامشخص','bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $badge }}">{{ $statusText }}</span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="p-4 rounded-md bg-gray-50">
            <div class="text-xs text-gray-500">جمع اقلام</div>
            <div class="text-lg font-semibold">{{ number_format($purchaseOrder->total_amount, 0) }} ریال</div>
        </div>

        @if(($purchaseOrder->vat_percent ?? 0) > 0)
            <div class="p-4 rounded-md bg-gray-50">
                <div class="text-xs text-gray-500">
                    ارزش افزوده ({{ rtrim(rtrim(number_format($purchaseOrder->vat_percent, 2), '0'), '.') }}%)
                </div>
                <div class="text-lg font-semibold">
                    {{ number_format($purchaseOrder->vat_amount ?? 0, 0) }} ریال
                </div>
            </div>
            <div class="p-4 rounded-md bg-gray-50">
                <div class="text-xs text-gray-500">
                    جمع با ارزش افزوده
                </div>
                <div class="text-lg font-semibold">
                    {{ number_format($purchaseOrder->total_with_vat ?? ($purchaseOrder->total_amount + ($purchaseOrder->vat_amount ?? 0)), 0) }} ریال
                </div>
            </div>
        @endif

        <div class="p-4 rounded-md bg-gray-50">
            <div class="text-xs text-gray-500">پیش‌پرداخت</div>
            <div class="text-lg font-semibold">{{ number_format($purchaseOrder->previously_paid_amount ?? 0, 0) }} ریال</div>
        </div>
        <div class="p-4 rounded-md bg-gray-50">
            <div class="text-xs text-gray-500">مانده قابل پرداخت</div>
            <div class="text-lg font-semibold">{{ number_format($purchaseOrder->remaining_payable_amount ?? 0, 0) }} ریال</div>
        </div>
    </div>

    @if(!empty($purchaseOrder->description))
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">توضیحات و اطلاعات حساب بانکی</h2>
            <div class="whitespace-pre-line text-gray-800">{{ $purchaseOrder->description }}</div>
        </div>
    @endif
</div>

<div class="bg-white rounded-lg shadow-md p-6 mt-4 font-vazirmatn" lang="fa" dir="rtl">
    @php \App\Helpers\DateHelper::class; @endphp
    @php
        $createdAtFa = \App\Helpers\DateHelper::toJalali($purchaseOrder->created_at, 'H:i Y/m/d');
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
        $currentUserId   = (int) auth()->id();
        $firstApproverId  = (int) (optional($poSettings ?? null)->first_approver_id ?? 0);
        $secondApproverId = (int) (optional($poSettings ?? null)->second_approver_id ?? 0);
        $accountingId     = (int) (optional($poSettings ?? null)->accounting_user_id ?? 0);

        $isFirstApprover  = $currentUserId === $firstApproverId;
        $isSecondApprover = $currentUserId === $secondApproverId;
        $isAccounting     = $currentUserId === $accountingId;
        $isCreator        = $currentUserId === (int) ($purchaseOrder->requested_by ?? 0);

        $showDecisionButtons = (
            (in_array($purchaseOrder->status, ['created','supervisor_approval'], true) && $isFirstApprover) ||
            ($purchaseOrder->status === 'manager_approval' && $isSecondApprover) ||
            ($purchaseOrder->status === 'accounting_approval' && $isAccounting)
        );
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
                {{ $a1AtFa ?: '—' }} @if($a1?->approver) — {{ $a1->approver->name }} @endif
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">تأیید مدیر کل</span>
            <span class="font-medium">
                {{ $a2AtFa ?: '—' }} @if($a2?->approver) — {{ $a2->approver->name }} @endif
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">تأیید حسابداری / پرداخت</span>
            <span class="font-medium">
                {{ $a3AtFa ?: '—' }} @if($a3?->approver) — {{ $a3->approver->name }} @endif
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
