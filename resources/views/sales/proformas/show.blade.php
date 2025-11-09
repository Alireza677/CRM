@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        [
            'title' => 'پیش‌فاکتورها',
            'url'   => route('sales.proformas.index')
        ],
        [
            'title' => 'جزئیات پیش‌فاکتور ' . ($proforma->subject ?? '#'.$proforma->id)
        ]
    ];
@endphp

@if(session('alert_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'توجه',
                text: "{{ session('alert_error') }}",
                confirmButtonText: 'باشه'
            });
        });
    </script>
@endif

<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- وضعیت و برچسب‌ها --}}
        <div class="flex items-center gap-2 mb-4">
        @php
            $stageKey = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;
            $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$stageKey] ?? 'نامشخص';
        @endphp

        <span class="px-2 py-1 rounded bg-gray-100 text-gray-800 text-sm">
            وضعیت: {{ $stageLabel }}
        </span>

            @if(($proforma->approval_mode ?? null) === 'override')
                <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">
                    Override (تأیید ادمین جایگزین)
                </span>
            @endif
        </div>

        @php
            // اگر متد pendingApproval در مدل اضافه شده از همان استفاده می‌کنیم؛
            // در غیر این صورت، همین‌جا pending را می‌گیریم.
            $pending = method_exists($proforma, 'pendingApproval')
                ? $proforma->pendingApproval()
                : $proforma->approvals()->with('approver')
                    ->where('status', 'pending')
                    ->orderBy('step')->orderBy('id')
                    ->first();
        @endphp

        @if($pending)
            <div class="mt-2 mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded">
                پیش‌فاکتور در انتظار تایید
                <strong>{{ optional($pending->approver)->name ?: ('کاربر #' . $pending->user_id) }}</strong>
                است.
            </div>
        @elseif(($proforma->approval_stage ?? $proforma->proforma_stage) === 'approved')
            <div class="mt-2 mb-4 p-4 bg-green-100 border border-green-400 text-green-800 rounded">
                پیش‌فاکتور تایید نهایی شد.
            </div>
        @endif

        {{-- عنوان و دکمه‌ها --}}
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">جزئیات پیش‌فاکتور</h2>
            <div class="flex gap-3">
                @can('update', $proforma)
                    <a href="{{ route('sales.proformas.edit', $proforma) }}" class="btn btn-primary">✏️ ویرایش</a>
                @endcan
                <a href="{{ route('sales.proformas.preview', $proforma) }}" class="btn btn-secondary">پیشنمایش چاپ</a>
                <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary">⬅ بازگشت</a>
            </div>
        </div>

        {{-- پیام‌ها --}}
        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- محتوای اصلی --}}
        <div class="relative">
            {{-- Timeline absolute خارج از باکس سفید --}}
            @php /** @var \App\Models\Proforma $proforma */ @endphp
            @php
                $createdAtFa = \App\Helpers\DateHelper::toJalali($proforma->created_at, 'H:i Y/m/d');

                $firstApproval = $proforma->approvals()
                    ->with('approver')
                    ->where('status', 'approved')
                    ->where('step', 1)
                    ->orderByDesc('approved_at')
                    ->first();
                $firstApprovedAt   = $firstApproval?->approved_at;
                $firstApprovedAtFa = $firstApprovedAt ? \App\Helpers\DateHelper::toJalali($firstApprovedAt, 'H:i Y/m/d') : null;
                $firstApproverName = $firstApproval?->approver?->name ?? $proforma->firstApprovedBy?->name;

                $secondApproval = $proforma->approvals()
                    ->with('approver')
                    ->where('status', 'approved')
                    ->where('step', 2)
                    ->orderByDesc('approved_at')
                    ->first();
                $secondApprovedAt   = $secondApproval?->approved_at;
                $secondApprovedAtFa = $secondApprovedAt ? \App\Helpers\DateHelper::toJalali($secondApprovedAt, 'H:i Y/m/d') : null;
                $secondApproverName = $secondApproval?->approver?->name ?? $proforma->approvedBy?->name;

                if (!$secondApprovedAt && ($proforma->approval_stage === 'approved' || $proforma->proforma_stage === 'approved')) {
                    $lastApproved = $proforma->approvals()
                        ->with('approver')
                        ->where('status', 'approved')
                        ->orderByDesc('approved_at')
                        ->first();
                    if ($lastApproved) {
                        $secondApprovedAt   = $lastApproved->approved_at;
                        $secondApprovedAtFa = $secondApprovedAt ? \App\Helpers\DateHelper::toJalali($secondApprovedAt, 'H:i Y/m/d') : null;
                        $secondApproverName = $secondApproverName ?: ($lastApproved->approver?->name);
                    }
                }

                $durationText = null;
                try {
                    if ($proforma->created_at && $secondApprovedAt) {
                        $minutes = $proforma->created_at->diffInMinutes($secondApprovedAt);
                        $days    = intdiv($minutes, 60*24);
                        $hours   = intdiv($minutes % (60*24), 60);
                        $mins    = $minutes % 60;
                        $parts = [];
                        if ($days)  $parts[] = $days . ' روز';
                        if ($hours) $parts[] = $hours . ' ساعت';
                        if ($mins && $days === 0) $parts[] = $mins . ' دقیقه';
                        $durationText = implode(' و ', $parts);
                    }
                } catch (\Throwable $e) { $durationText = null; }

                $hasFirst  = !empty($firstApprovedAtFa);
                $hasSecond = !empty($secondApprovedAtFa);
                $dotFirstClass  = $hasFirst  ? 'bg-green-600' : 'bg-gray-400';
                $dotSecondClass = $hasSecond ? 'bg-green-600' : 'bg-gray-400';
            @endphp

            <div class="hidden lg:block absolute top-0 -right-80 w-72">
                <div class="bg-white/70 backdrop-blur-sm border rounded-xl shadow p-4">
                    <div class="text-center text-sm font-semibold text-gray-800 mb-2">تایم‌لاین تاییدات</div>
                    <ol class="relative border-r-2 border-gray-200 pr-5 space-y-6">
                        <li class="relative pr-6">
                            <span class="absolute -right-[7px] top-1 w-3 h-3 bg-blue-600 rounded-full border-2 border-white shadow"></span>
                            <div class="text-xs text-gray-500">{{ $createdAtFa ?: '—' }}</div>
                            <div class="text-sm font-medium">ثبت پیش‌فاکتور</div>
                        </li>
                        <li class="relative pr-6">
                            <span class="absolute -right-[7px] top-1 w-3 h-3 {{ $dotFirstClass }} rounded-full border-2 border-white shadow"></span>
                            <div class="text-xs text-gray-500">{{ $firstApprovedAtFa ?: 'در انتظار' }}</div>
                            <div class="text-sm font-medium">تایید اول
                                @if($firstApproverName)
                                    <span class="text-gray-500">— {{ $firstApproverName }}</span>
                                @endif
                            </div>
                        </li>
                        <li class="relative pr-6">
                            <span class="absolute -right-[7px] top-1 w-3 h-3 {{ $dotSecondClass }} rounded-full border-2 border-white shadow"></span>
                            <div class="text-xs text-gray-500">{{ $secondApprovedAtFa ?: 'در انتظار' }}</div>
                            <div class="text-sm font-medium">تایید نهایی
                                @if($secondApproverName)
                                    <span class="text-gray-500">— {{ $secondApproverName }}</span>
                                @endif
                            </div>
                        </li>
                    </ol>
                    @if($durationText)
                        <div class="mt-3 text-center text-xs">
                            <span class="inline-block rounded bg-gray-100 px-2 py-1 text-gray-700">مدت زمان تا تایید نهایی: <strong>{{ $durationText }}</strong></span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- اطلاعات خلاصه در ۳ باکس --}}
                <div class="md:col-span-2">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        {{-- Box 1: اطلاعات پایه --}}
                        <div class="lg:col-span-4 rounded-2xl border border-green-200 bg-green-50/80 shadow-sm hover:shadow-md transition">
                            <div class="p-5">
                                <h3 class="text-base font-semibold text-green-800 mb-3">اطلاعات پایه</h3>
                                @php
                                    use Morilog\Jalali\Jalalian;
                                    try {
                                        $shamsiDate = ($proforma->proforma_date instanceof \Carbon\Carbon)
                                            ? Jalalian::fromCarbon($proforma->proforma_date)->format('Y/m/d')
                                            : 'تاریخ نامعتبر';
                                    } catch (\Throwable $e) {
                                        $shamsiDate = 'تاریخ نامعتبر';
                                    }
                                    $stageKey   = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;
                                    $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$stageKey] ?? 'نامشخص';
                                @endphp
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">موضوع</span>
                                        <span class="font-medium text-gray-900">{{ $proforma->subject ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">تاریخ پیش‌فاکتور</span>
                                        <span class="font-medium text-gray-900">{{ $shamsiDate }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">شماره پیش‌فاکتور</span>
                                        <span class="font-medium text-gray-900">{{ $proforma->proforma_number ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">مرحله</span>
                                        <span class="font-medium text-gray-900">{{ $stageLabel }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Box 2: اطلاعات تماس --}}
                        <div class="lg:col-span-4 rounded-2xl border border-sky-200 bg-sky-50/80 shadow-sm hover:shadow-md transition">
                            <div class="p-5">
                                <h3 class="text-base font-semibold text-sky-800 mb-3">اطلاعات تماس</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">نام مخاطب</span>
                                        <span class="font-medium text-gray-900">
                                            @if($proforma->contact)
                                                <a href="{{ route('sales.contacts.show', $proforma->contact) }}" class="text-blue-600 hover:underline">
                                                    {{ $proforma->contact->name ?? $proforma->contact_name }}
                                                </a>
                                            @else
                                                {{ $proforma->contact_name ?? '—' }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">نام سازمان</span>
                                        <span class="font-medium text-gray-900">
                                            @if($proforma->organization)
                                                <a href="{{ route('sales.organizations.show', $proforma->organization) }}" class="text-blue-600 hover:underline">
                                                    {{ $proforma->organization->name ?? $proforma->organization_name }}
                                                </a>
                                            @else
                                                {{ $proforma->organization_name ?? '—' }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">ارجاع به</span>
                                        <span class="font-medium text-gray-900">{{ $proforma->assignedTo?->name ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">فرصت فروش</span>
                                        <span class="font-medium text-gray-900">
                                            @if($proforma->opportunity)
                                                <a href="{{ route('sales.opportunities.show', $proforma->opportunity) }}" class="text-blue-600 hover:underline">
                                                    {{ $proforma->opportunity->name ?? ('فرصت #' . $proforma->opportunity->id) }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Box 3: اطلاعات آدرس --}}
                        <div class="lg:col-span-4 rounded-2xl border border-violet-200 bg-violet-50/80 shadow-sm hover:shadow-md transition">
                            <div class="p-5">
                                <h3 class="text-base font-semibold text-violet-800 mb-3">اطلاعات آدرس</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">نوع آدرس</span>
                                        <span class="font-medium text-gray-900">
                                            {{ $proforma->address_type === 'invoice' ? 'آدرس تحویل صورت‌حساب' : 'آدرس تحویل محصول' }}
                                        </span>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="text-gray-600">آدرس</span>
                                        <span class="font-medium text-gray-900 text-left whitespace-pre-line">{{ $proforma->customer_address ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">شهر</span>
                                        <span class="font-medium text-gray-900">{{ $proforma->city ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">استان</span>
                                        <span class="font-medium text-gray-900">{{ $proforma->state ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sidebar: تایملاین/اطلاعات تاییدات --}}
                <div class="hidden">
                    @php /** @var \App\Models\Proforma $proforma */ @endphp
                    @php
                        // تاریخ ثبت (ایجاد)
                        $createdAtFa = \App\Helpers\DateHelper::toJalali($proforma->created_at, 'H:i Y/m/d');

                        // تایید اول
                        $firstApproval = $proforma->approvals()
                            ->with('approver')
                            ->where('status', 'approved')
                            ->where('step', 1)
                            ->orderByDesc('approved_at')
                            ->first();
                        $firstApprovedAtFa = $firstApproval?->approved_at ? \App\Helpers\DateHelper::toJalali($firstApproval->approved_at, 'H:i Y/m/d') : null;
                        $firstApproverName = $firstApproval?->approver?->name ?? $proforma->firstApprovedBy?->name;

                        // تایید دوم (نهایی)
                        $secondApproval = $proforma->approvals()
                            ->with('approver')
                            ->where('status', 'approved')
                            ->where('step', 2)
                            ->orderByDesc('approved_at')
                            ->first();
                        $secondApprovedAt   = $secondApproval?->approved_at;
                        $secondApprovedAtFa = $secondApprovedAt ? \App\Helpers\DateHelper::toJalali($secondApprovedAt, 'H:i Y/m/d') : null;
                        $secondApproverName = $secondApproval?->approver?->name ?? $proforma->approvedBy?->name;

                        // در صورت نبود مرحله دوم ولی تایید نهایی انجام شده
                        if (!$secondApprovedAt && ($proforma->approval_stage === 'approved' || $proforma->proforma_stage === 'approved')) {
                            $lastApproved = $proforma->approvals()
                                ->with('approver')
                                ->where('status', 'approved')
                                ->orderByDesc('approved_at')
                                ->first();
                            if ($lastApproved) {
                                $secondApprovedAt   = $lastApproved->approved_at;
                                $secondApprovedAtFa = $secondApprovedAt ? \App\Helpers\DateHelper::toJalali($secondApprovedAt, 'H:i Y/m/d') : null;
                                $secondApproverName = $secondApproverName ?: ($lastApproved->approver?->name);
                            }
                        }

                        // مدت زمان از ایجاد تا تایید نهایی
                        $durationText = null;
                        try {
                            if ($proforma->created_at && $secondApprovedAt) {
                                $minutes = $proforma->created_at->diffInMinutes($secondApprovedAt);
                                $days    = intdiv($minutes, 60*24);
                                $hours   = intdiv($minutes % (60*24), 60);
                                $mins    = $minutes % 60;
                                $parts = [];
                                if ($days)  $parts[] = $days . ' روز';
                                if ($hours) $parts[] = $hours . ' ساعت';
                                if ($mins && $days === 0) $parts[] = $mins . ' دقیقه';
                                $durationText = implode(' و ', $parts);
                            }
                        } catch (\Throwable $e) { $durationText = null; }
                    @endphp

                    <div class="bg-gray-50 border rounded-lg p-4">
                        <h3 class="text-base font-semibold mb-3">پیگیری تاییدات</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاریخ ثبت پیش‌فاکتور</span>
                                <span class="font-medium">{{ $createdAtFa ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاریخ تایید اول</span>
                                <span class="font-medium">{{ $firstApprovedAtFa ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاییدکننده اول</span>
                                <span class="font-medium">{{ $firstApproverName ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاریخ تایید دوم</span>
                                <span class="font-medium">{{ $secondApprovedAtFa ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاییدکننده دوم</span>
                                <span class="font-medium">{{ $secondApproverName ?: '—' }}</span>
                            </div>
                            @if($durationText)
                                <div class="flex justify-between pt-2 border-t mt-2">
                                    <span class="text-gray-700">مدت زمان تا تایید نهایی</span>
                                    <span class="font-semibold text-gray-900">{{ $durationText }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- اطلاعات تماس: منتقل شد به باکس‌ها --}}

                {{-- اطلاعات آدرس: منتقل شد به باکس‌ها --}}

                {{-- اطلاعات محصول --}}
                <div class="space-y-4 md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">
                    اطلاعات محصول 
                    <span style="font-size:14px">(قیمت‌ها به ریال می‌باشد)</span>
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-center">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th>نام محصول</th>
                                    <th>تعداد</th>
                                    <th>واحد</th>
                                    <th>قیمت واحد</th>
                                    <th>جمع ردیف</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // جمع سطری‌ها فقط برای نمایش ردیف‌ها
                                    $subtotal = 0;
                                @endphp

                                @foreach($proforma->items as $item)
                                    @php
                                        $subtotal += (float)($item->total_price ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            @switch($item->unit_of_use)
                                                @case('device') دستگاه @break
                                                @case('piece')  عدد    @break
                                                @case('meter')  متر    @break
                                                @default {{ $item->unit_of_use }}
                                            @endswitch
                                        </td>
                                        <td>{{ number_format((float)$item->unit_price, 0) }}</td>
                                        <td>{{ number_format((float)$item->total_price, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>

                            @php
                                // اگر در هدر ذخیره شده، همان را بخوان؛ در غیر این‌صورت محاسبه کن
                                $discType = $proforma->global_discount_type ?? null;
                                $discVal  = (float)($proforma->global_discount_value ?? 0);
                                $taxType  = $proforma->global_tax_type ?? null;
                                $taxVal   = (float)($proforma->global_tax_value ?? 0);

                                // مقدار نهایی تخفیف
                                if (isset($proforma->global_discount_amount)) {
                                    $discount = (float)$proforma->global_discount_amount;
                                } else {
                                    $discount = $discType === 'percentage' ? ($subtotal * $discVal) / 100
                                            : ($discType === 'fixed' ? $discVal : 0);
                                }
                                $discount = min($discount, $subtotal);
                                $afterDiscount = $subtotal - $discount;

                                // مقدار نهایی مالیات
                                if (isset($proforma->global_tax_amount)) {
                                    $tax = (float)$proforma->global_tax_amount;
                                } else {
                                    $tax = $taxType === 'percentage' ? ($afterDiscount * $taxVal) / 100
                                        : ($taxType === 'fixed' ? $taxVal : 0);
                                }
                                $tax = max($tax, 0);

                                // مجموع کل
                                $grand = isset($proforma->total_amount)
                                    ? (float)$proforma->total_amount
                                    : ($afterDiscount + $tax);
                            @endphp

                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="font-bold text-right">جمع پایه (بدون تخفیف/مالیات):</td>
                                    <td class="font-bold">{{ number_format($subtotal, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="font-bold text-right">جمع تخفیف (سراسری):</td>
                                    <td class="font-bold text-red-600">{{ number_format($discount, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="font-bold text-right">جمع مالیات (سراسری):</td>
                                    <td class="font-bold text-green-600">{{ number_format($tax, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="font-bold text-right">مجموع کل:</td>
                                    <td class="font-bold">{{ number_format($grand, 0) }}</td>
                                </tr>
                            </tfoot>

                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- دکمه‌ها پایین صفحه --}}
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary">⬅ بازگشت به لیست</a>

                @can('approve', $proforma)
                    <form action="{{ route('sales.proformas.approve', $proforma) }}" method="POST"
                        onsubmit="return confirm('آیا از تایید پیش‌فاکتور مطمئن هستید؟');">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            ✅ تایید پیش‌فاکتور
                        </button>
                        @if(optional($proforma->automationRule)->emergency_approver_id === auth()->id())
                            <div class="text-xs text-yellow-700 mt-2">
                                شما تأییدکنندهٔ جایگزین هستید؛ تایید شما نهایی است.
                            </div>
                        @endif
                    </form>

                    <form action="{{ route('sales.proformas.reject', $proforma) }}" method="POST"
                        onsubmit="return confirm('آیا از رد این پیش‌فاکتور مطمئن هستید؟ با رد کردن، کل فرایند متوقف می‌شود.');"
                        class="ml-2">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            ❌ رد پیش‌فاکتور
                        </button>
                    </form>
                @endcan
            </div>

    </div>
</div>

<style>
    .btn {
        display: inline-block;
        font-weight: 500;
        text-align: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease-in-out;
    }
    .btn-primary { color: white; background-color: #2563eb; }
    .btn-primary:hover { background-color: #1d4ed8; }
    .btn-secondary { color: white; background-color: #6b7280; }
    .btn-secondary:hover { background-color: #4b5563; }
    .btn-success { color: white; background-color: #16a34a; }
    .btn-success:hover { background-color: #15803d; }
</style>

@endsection
