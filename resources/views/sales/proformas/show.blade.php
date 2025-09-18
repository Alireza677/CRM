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
                text: '{{ session('alert_error') }}',
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

                {{-- دکمه ویرایش فقط اگر مجاز باشد (Policy:update) --}}
                @can('update', $proforma)
                    <a href="{{ route('sales.proformas.edit', $proforma) }}" class="btn btn-primary">✏️ ویرایش</a>
                @endcan

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
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- اطلاعات پایه --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات پایه</h3>
                    <div><strong>موضوع:</strong> {{ $proforma->subject }}</div>

                    @php
                        use Morilog\Jalali\Jalalian;

                        try {
                            $shamsiDate = ($proforma->proforma_date instanceof \Carbon\Carbon)
                                ? Jalalian::fromCarbon($proforma->proforma_date)->format('Y/m/d')
                                : 'تاریخ نامعتبر';
                        } catch (\Throwable $e) {
                            $shamsiDate = 'تاریخ نامعتبر';
                        }
                    @endphp

                    <div><strong>تاریخ پیش فاکتور:</strong> {{ $shamsiDate }}</div>
                    <div><strong>شماره پیش فاکتور:</strong> {{ $proforma->proforma_number }}</div>
                    @php
                        $stageKey   = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;
                        $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$stageKey] ?? 'نامشخص';
                    @endphp

                    <div>
                        <strong>مرحله:</strong> {{ $stageLabel }}
                    </div>

                    {{-- نمایش اطلاعات تایید (در صورت وجود) --}}
                    @if(!empty($proforma->first_approved_by))
                        <div>
                            <strong>تایید مرحله اول توسط:</strong>
                            {{ optional($proforma->firstApprovedBy)->name ?? '—' }}
                        </div>
                    @endif

                    @if(!empty($proforma->approved_by))
                        <div>
                            <strong>تایید نهایی توسط:</strong>
                            {{ optional($proforma->approvedBy)->name ?? '—' }}
                        </div>
                    @endif

                </div>

                {{-- اطلاعات تماس --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات تماس</h3>

                    {{-- مخاطب --}}
                    <div>
                        <strong>نام مخاطب:</strong>
                        @if($proforma->contact)
                            <a href="{{ route('sales.contacts.show', $proforma->contact) }}" class="text-blue-600 hover:underline">
                                {{ $proforma->contact->name ?? $proforma->contact_name }}
                            </a>
                        @else
                            {{ $proforma->contact_name }}
                        @endif
                    </div>

                    {{-- سازمان --}}
                    <div>
                        <strong>نام سازمان:</strong>
                        @if($proforma->organization)
                            <a href="{{ route('sales.organizations.show', $proforma->organization) }}" class="text-blue-600 hover:underline">
                                {{ $proforma->organization->name ?? $proforma->organization_name }}
                            </a>
                        @else
                            {{ $proforma->organization_name }}
                        @endif
                    </div>

                    {{-- ارجاع --}}
                    <div><strong>ارجاع به:</strong> {{ $proforma->assignedTo?->name }}</div>

                    {{-- فرصت فروش --}}
                    <div>
                        <strong>فرصت فروش:</strong>
                        @if($proforma->opportunity)
                            <a href="{{ route('sales.opportunities.show', $proforma->opportunity) }}" class="text-blue-600 hover:underline">
                                {{ $proforma->opportunity->name ?? ('فرصت #' . $proforma->opportunity->id) }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                {{-- اطلاعات آدرس --}}
                <div class="space-y-4 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات آدرس</h3>
                    <div><strong>نوع آدرس:</strong>
                        {{ $proforma->address_type === 'invoice' ? 'آدرس تحویل صورت‌حساب' : 'آدرس تحویل محصول' }}
                    </div>
                    <div><strong>آدرس:</strong> {{ $proforma->customer_address }}</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><strong>شهر:</strong> {{ $proforma->city }}</div>
                        <div><strong>استان:</strong> {{ $proforma->state }}</div>
                    </div>
                </div>

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
                                    <th> جمع ردیف</th>
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
                                    <td colspan="6" class="font-bold text-right">جمع پایه (بدون تخفیف/مالیات):</td>
                                    <td class="font-bold">{{ number_format($subtotal, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="font-bold text-right">جمع تخفیف (سراسری):</td>
                                    <td class="font-bold text-red-600">{{ number_format($discount, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="font-bold text-right">جمع مالیات (سراسری):</td>
                                    <td class="font-bold text-green-600">{{ number_format($tax, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="font-bold text-right">مجموع کل:</td>
                                    <td class="font-bold">{{ number_format($grand, 0) }}</td>
                                </tr>
                            </tfoot>

                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- دکمه‌ها پایین صفحه --}}
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary">⬅ بازگشت به لیست</a>

                {{-- دکمه تایید: فقط وقتی مجاز باشم (Policy:approve) --}}
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

                    {{-- دکمه رد --}}
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
