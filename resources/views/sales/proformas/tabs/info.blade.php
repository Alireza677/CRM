{{-- هشدار وضعیت تأیید --}}
@if($pending)
    <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg flex items-start gap-3">
        <span class="mt-0.5 text-amber-500">
            <i class="fas fa-exclamation-triangle"></i>
        </span>
        <div>
            این پیش‌فاکتور در انتظار تأیید توسط
            <strong>{{ optional($pending->approver)->name ?: ('کاربر #' . $pending->user_id) }}</strong>
            است.
        </div>
    </div>
{{-- پیام تأیید نهایی --}}
@elseif(($proforma->approval_stage ?? $proforma->proforma_stage) === 'approved')
    <div class="alert-box p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start gap-3 relative" data-alert-key="proforma_{{ $proforma->id }}_approved">
        <button class="alert-close absolute top-2 left-2 text-green-600 hover:text-green-800 text-xl leading-none">&times;</button>

        <span class="mt-0.5 text-green-500">
            <i class="fas fa-check-circle"></i>
        </span>
        <div>فرآیند تأیید این پیش‌فاکتور با موفقیت تکمیل شده است.</div>
    </div>
@endif


{{-- هشدار تأییدهای باطل‌شده --}}
@if($proforma->approvals()->where('status', 'superseded')->count())
    <div class="alert-box p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg flex items-start gap-3 relative" data-alert-key="proforma_{{ $proforma->id }}_superseded">
        <button class="alert-close absolute top-2 left-2 text-amber-600 hover:text-amber-800 text-xl leading-none">&times;</button>

        <span class="mt-0.5 text-amber-500">
            <i class="fas fa-info-circle"></i>
        </span>
        <div>تأییدهای قبلی پس از آخرین ویرایش، باطل شده‌اند و گردش تأیید از ابتدا آغاز شده است.</div>
    </div>
@endif


{{-- پیام‌های موفقیت/خطا --}}
@if (session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
@endif

<div class="relative lg:pr-40">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 space-y-4">
            {{-- سه باکس اطلاعات اصلی --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                {{-- باکس اطلاعات پیش‌فاکتور --}}
                <div class="lg:col-span-4 rounded-2xl border border-green-200 bg-green-50/80 shadow-sm hover:shadow-md transition">
                    <div class="p-5 space-y-2 text-sm">
                        <h3 class="text-base font-semibold text-green-800 mb-3">
                            اطلاعات پایه پیش‌فاکتور
                        </h3>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">موضوع</span>
                            <span class="font-medium text-gray-900">
                                {{ $proforma->subject ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">تاریخ پیش‌فاکتور</span>
                            <span class="font-medium text-gray-900">
                                {{ $shamsiDate }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">شماره پیش‌فاکتور</span>
                            <span class="font-medium text-gray-900">
                                {{ $proforma->proforma_number ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">وضعیت</span>
                            <span class="font-medium text-gray-900">
                                {{ $stageLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- باکس اطلاعات مشتری --}}
                <div class="lg:col-span-4 rounded-2xl border border-sky-200 bg-sky-50/80 shadow-sm hover:shadow-md transition">
                    <div class="p-5 space-y-2 text-sm">
                        <h3 class="text-base font-semibold text-sky-800 mb-3">
                            اطلاعات مشتری
                        </h3>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">شخص تماس</span>
                            <span class="font-medium text-gray-900">
                                @if($proforma->contact)
                                    <a href="{{ route('sales.contacts.show', $proforma->contact) }}"
                                       class="text-blue-600 hover:underline">
                                        {{ $proforma->contact->name ?? $proforma->contact_name }}
                                    </a>
                                @else
                                    {{ $proforma->contact_name ?? '—' }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">سازمان / شرکت</span>
                            <span class="font-medium text-gray-900">
                                @if($proforma->organization)
                                    <a href="{{ route('sales.organizations.show', $proforma->organization) }}"
                                       class="text-blue-600 hover:underline">
                                        {{ $proforma->organization->name ?? $proforma->organization_name }}
                                    </a>
                                @else
                                    {{ $proforma->organization_name ?? '—' }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">مسئول پیگیری</span>
                            <span class="font-medium text-gray-900">
                                {{ $proforma->assignedTo?->name ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">فرصت فروش مرتبط</span>
                            <span class="font-medium text-gray-900">
                                @if($proforma->opportunity)
                                    <a href="{{ route('sales.opportunities.show', $proforma->opportunity) }}"
                                       class="text-blue-600 hover:underline">
                                        {{ $proforma->opportunity->name ?? ('فرصت فروش #' . $proforma->opportunity->id) }}
                                    </a>
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- باکس اطلاعات آدرس --}}
                <div class="lg:col-span-4 rounded-2xl border border-violet-200 bg-violet-50/80 shadow-sm hover:shadow-md transition">
                    <div class="p-5 space-y-2 text-sm">
                        <h3 class="text-base font-semibold text-violet-800 mb-3">
                            اطلاعات آدرس
                        </h3>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">نوع آدرس</span>
                            <span class="font-medium text-gray-900">
                                {{ $proforma->address_type === 'invoice'
                                    ? 'آدرس صورتحساب'
                                    : 'آدرس ارسال / پروژه' }}
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-600">آدرس</span>
                            <span class="font-medium text-gray-900 text-left whitespace-pre-line">
                                {{ $proforma->customer_address ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">شهر</span>
                            <span class="font-medium text-gray-900">
                                {{ $proforma->city ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">استان</span>
                            <span class="font-medium text-gray-900">
                                {{ $proforma->state ?? '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- باکس خلاصه مبالغ --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50/80 shadow-sm hover:shadow-md transition">
                <div class="p-5">
                    <h3 class="text-base font-semibold text-amber-800 mb-3">
                        خلاصه مبالغ
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-3 rounded-lg bg-white/60 border border-amber-100">
                            <div class="text-xs text-gray-500">جمع جزء پیش‌فاکتور</div>
                            <div class="text-lg font-bold text-gray-900">
                                {{ number_format($subtotal, 0) }} ریال
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-white/60 border border-amber-100">
                            <div class="text-xs text-gray-500">مبلغ تخفیف کلی</div>
                            <div class="text-lg font-bold text-gray-900">
                                {{ number_format($discount, 0) }} ریال
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-white/60 border border-amber-100">
                            <div class="text-xs text-gray-500">مبلغ مالیات کلی</div>
                            <div class="text-lg font-bold text-gray-900">
                                {{ number_format($tax, 0) }} ریال
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                            مبلغ نهایی قابل پرداخت:
                            {{ number_format($grand, 0) }} ریال
                        </span>
                        @if(($proforma->global_discount_type ?? null) === 'percentage')
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                                درصد تخفیف کلی:
                                {{ rtrim(rtrim(number_format($proforma->global_discount_value ?? 0, 2), '0'), '.') }}%
                            </span>
                        @endif
                        @if(($proforma->global_tax_type ?? null) === 'percentage')
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                                درصد مالیات کلی:
                                {{ rtrim(rtrim(number_format($proforma->global_tax_value ?? 0, 2), '0'), '.') }}%
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- توضیحات --}}
            @if(!empty($proforma->description))
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                    <h3 class="text-base font-semibold mb-2 text-gray-800">
                        توضیحات و یادداشت‌های پیش‌فاکتور
                    </h3>
                    <div class="whitespace-pre-line text-gray-800">
                        {{ $proforma->description }}
                    </div>
                </div>
            @endif
        </div>

       
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-box[data-alert-key]').forEach(box => {
        const key = box.dataset.alertKey;
        const storageKey = 'crm_alert_hidden_' + key;

        if (localStorage.getItem(storageKey) === '1') {
            box.style.display = 'none';
            return;
        }

        const closeBtn = box.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                box.style.display = 'none';
                localStorage.setItem(storageKey, '1');
            });
        }
    });
});
</script>
