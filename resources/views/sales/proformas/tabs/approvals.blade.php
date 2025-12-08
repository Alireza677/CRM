<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
    <h3 class="text-md font-semibold mb-3">
        وضعیت مراحل تأیید پیش‌فاکتور
    </h3>

    <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2 text-right">مرحله</th>
                <th class="border p-2 text-right">وضعیت</th>
                <th class="border p-2 text-right">تاریخ و ساعت</th>
                <th class="border p-2 text-right">تأییدکننده اصلی</th>
                <th class="border p-2 text-right">جانشین تأییدکننده</th>
            </tr>
        </thead>
        <tbody>
            {{-- ردیف ثبت درخواست --}}
            <tr>
                <td class="border p-2">
                    ثبت درخواست پیش‌فاکتور
                </td>
                <td class="border p-2 bg-blue-50 text-blue-800">
                    ثبت شده توسط کاربر
                </td>
                <td class="border p-2">
                    {{ $createdAtFa ?: '—' }}
                </td>
                <td class="border p-2">
                    {{ optional($proforma->requestedByUser)->name ?: '—' }}
                </td>
                <td class="border p-2">—</td>
            </tr>

            {{-- ردیف تأیید مرحله اول --}}
            <tr>
                <td class="border p-2">
                    تأیید مرحله اول
                </td>
                <td class="border p-2 {{ $a1StatusClass }}">
                    {{ $a1StatusLabel }}
                </td>
                <td class="border p-2">
                    {{ $a1DateDisplay }}
                </td>
                <td class="border p-2 {{ $firstMainCellClass }}">
                    {{ $firstApproverName }}
                </td>
                <td class="border p-2 {{ $firstSubCellClass }}">
                    {{ $firstApproverSubstituteName }}
                </td>
            </tr>

            {{-- ردیف تأیید مرحله دوم --}}
            <tr>
                <td class="border p-2">
                    تأیید مرحله دوم
                </td>
                <td class="border p-2 {{ $a2StatusClass }}">
                    {{ $a2StatusLabel }}
                </td>
                <td class="border p-2">
                    {{ $a2DateDisplay }}
                </td>
                <td class="border p-2 {{ $secondMainCellClass }}">
                    {{ $secondApproverName }}
                </td>
                <td class="border p-2 {{ $secondSubCellClass }}">
                    {{ $secondApproverSubstituteName }}
                </td>
            </tr>
        </tbody>
    </table>

    @if($durationText)
        <div class="flex justify-between pt-2 text-xs text-gray-600">
            <span>مدت زمان تا تأیید نهایی</span>
            <span class="font-semibold text-gray-900">{{ $durationText }}</span>
        </div>
    @endif
</div>

{{-- دکمه‌های تصمیم تأیید/رد --}}
@if($showDecisionButtons)
    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('sales.proformas.approve', $proforma) }}">
            @csrf
            <button type="submit"
                    class="px-4 py-2 rounded text-white bg-green-600 hover:bg-green-700 shadow">
                تأیید این پیش‌فاکتور
            </button>
        </form>

        <button type="button"
                    class="px-4 py-2 rounded text-white bg-red-600 hover:bg-red-700 shadow"
                    onclick="document.getElementById('rejectModal')?.classList.remove('hidden'); document.getElementById('rejectModal')?.classList.add('flex');">
            رد این پیش‌فاکتور
        </button>
    </div>
@elseif(!empty($pendingApproverName))
    <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 inline-flex items-center gap-2">
        <i class="fas fa-hourglass-half"></i>
        <span>در انتظار تأیید {{ $pendingApproverName }} است.</span>
    </div>
@elseif(($proforma->approval_stage ?? $proforma->proforma_stage) === 'approved')
    <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 inline-flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span>فرآیند تأیید این پیش‌فاکتور به طور کامل انجام شده است.</span>
    </div>
@endif

{{-- مودال رد پیش‌فاکتور --}}
<div id="rejectModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <h4 class="font-semibold text-gray-800">
                ثبت دلیل رد پیش‌فاکتور
            </h4>
            <button type="button"
                    class="text-gray-500 hover:text-gray-700"
                    onclick="document.getElementById('rejectModal')?.classList.add('hidden'); document.getElementById('rejectModal')?.classList.remove('flex');">
                ×
            </button>
        </div>
        <form method="POST" action="{{ route('sales.proformas.reject', $proforma) }}">
            @csrf
            <div class="p-4 space-y-3">
                <label class="block text-sm text-gray-700 mb-1">
                    لطفاً دلیل رد پیش‌فاکتور را وارد کنید
                </label>
                <textarea name="reject_reason"
                          required
                          maxlength="2000"
                          class="w-full border rounded p-2 min-h-[120px]"
                          placeholder="مثال: قیمت مورد تأیید مدیریت مالی نیست، مشخصات کالا نیاز به اصلاح دارد، یا سایر توضیحات..."></textarea>
            </div>
            <div class="px-4 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                <button type="button"
                        class="px-4 py-2 rounded bg-gray-200 text-gray-900 hover:bg-gray-300"
                        onclick="document.getElementById('rejectModal')?.classList.add('hidden'); document.getElementById('rejectModal')?.classList.remove('flex');">
                    انصراف
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded text-white bg-red-600 hover:bg-red-700">
                    ثبت رد پیش‌فاکتور
                </button>
            </div>
        </form>
    </div>
</div>
