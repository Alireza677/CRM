@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ایجاد فرصت جدید']
    ];
@endphp
@php use Illuminate\Support\Str; @endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">
            {{ __('فرصت جدید') }}
        </h2>

        <form method="POST" action="{{ route('sales.opportunities.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- عنوان --}}
                <div>
                    <label for="name" class="block font-medium text-sm text-gray-700 required">عنوان</label>
                    <input id="name" name="name" type="text"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm form-field"
                           value="{{ old('name') }}" required>
                    @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                {{-- سازمان --}}
                <div>
                    <label for="organization_id" class="block font-medium text-sm text-gray-700">سازمان</label>
                    <div class="flex items-center gap-2">
                    <input type="text" id="organization_name" name="organization_name"
                        class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                        placeholder="انتخاب سازمان" readonly>
                        <input type="hidden" id="organization_id" name="organization_id">
                        <button type="button" onclick="openOrganizationModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
                    </div>
                    @error('organization_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                {{-- مخاطب --}}
                <div>
                    <label for="contact_display" class="block font-medium text-sm text-gray-700">مخاطب</label>
                    <div class="relative">
                        <input type="text" id="contact_display" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                        placeholder="انتخاب مخاطب..." readonly
                            value="{{ old('contact_display') ?? ($defaultContact->full_name ?? '') }}">
                        <input type="hidden" name="contact_id" id="contact_id" value="{{ old('contact_id') ?? ($defaultContact->id ?? '') }}">
                        <button type="button" onclick="openContactModal()"
                                class="absolute inset-y-0 left-0 flex items-center px-3 text-gray-500 hover:text-blue-600">🔍</button>
                    </div>
                </div>


                {{-- سایر فیلدها بدون تغییر --}}
                <div>
                    <label for="type" class="block font-medium text-sm text-gray-700 ">نوع کسب‌وکار</label>
                    <select id="type" name="type" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        <option value="کسب و کار موجود" {{ old('type') == 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
                        <option value="کسب و کار جدید" {{ old('type') == 'کسب و کار جدید' ? 'selected' : '' }}>کسب و کار جدید</option>
                    </select>
                    @error('type') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="stage" class="block font-medium text-sm text-gray-700 required">مرحله فروش</label>
                    <select name="stage" id="stage"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید...</option>
                        <option value="در حال پیگیری" {{ old('stage') == 'در حال پیگیری' ? 'selected' : '' }}>در حال پیگیری</option>
                        <option value="پیگیری در آینده" {{ old('stage') == 'پیگیری در آینده' ? 'selected' : '' }}>پیگیری در آینده</option>
                        <option value="برنده" {{ old('stage') == 'برنده' ? 'selected' : '' }}>برنده</option>
                        <option value="بازنده" {{ old('stage') == 'بازنده' ? 'selected' : '' }}>بازنده</option>
                        <option value="سرکاری" {{ old('stage') == 'سرکاری' ? 'selected' : '' }}>سرکاری</option>
                        <option value="ارسال پیش فاکتور" {{ old('stage') == 'ارسال پیش فاکتور' ? 'selected' : '' }}>ارسال پیش فاکتور</option>
                    </select>
                    @error('stage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="source" class="block font-medium text-sm text-gray-700 required">منبع سرنخ</label>
                    <select id="source" name="source" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        <option value="وب سایت" {{ old('source') == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                        <option value="مشتریان قدیمی" {{ old('source') == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                        <option value="نمایشگاه" {{ old('source') == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                        <option value="بازاریابی حضوری" {{ old('source') == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                    </select>
                    @error('source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="assigned_to" class="block font-medium text-sm text-gray-700 required">ارجاع به</label>
                    <select id="assigned_to" name="assigned_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="success_rate" class="block font-medium text-sm text-gray-700 ">درصد موفقیت</label>
                    <input id="success_rate" name="success_rate" type="number" min="0" max="100"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           value="{{ old('success_rate') }}" required>
                    @error('success_rate') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

               

                <div class="md:col-span-2">
                    <label for="next_follow_up" class="block font-medium text-sm text-gray-700">تاریخ پیگیری بعدی</label>
                    <input type="text" id="next_follow_up_shamsi" class="form-control" placeholder="انتخاب تاریخ ">
                    <input type="hidden" name="next_follow_up" id="next_follow_up" value="{{ old('next_follow_up') }}">
                    @error('next_follow_up') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block font-medium text-sm text-gray-700">توضیحات</label>
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                    @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    ذخیره
                </button>
            </div>
        </form>
    </div>
</div>

{{-- مودال انتخاب مخاطب --}}
<div id="contactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
            <button onclick="closeContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        {{-- نوار جستجو --}}
        <div class="mb-3">
            <input
                id="contactSearchInput"
                type="text"
                placeholder="جستجوی نام یا موبایل…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                autocomplete="off"
            >
            <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
        </div>

        <div class="border border-gray-200 rounded overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
                        <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
                    </tr>
                </thead>
                <tbody id="contactTableBody">
                    @foreach($contacts as $c)
                        <tr class="cursor-pointer hover:bg-gray-50"
                            data-name="{{ $c->full_name }}"     {{-- بدون lowercase سمت سرور --}}
                            data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}"
                            onclick="selectContact({{ $c->id }}, @js($c->full_name))">
                            <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div id="contactNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
        </div>
    </div>
</div>



<!-- مودال انتخاب سازمان -->
<div id="organizationModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب سازمان</h3>
            <button onclick="closeOrganizationModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        {{-- نوار جستجو --}}
        <div class="mb-3">
            <input
                id="organizationSearchInput"
                type="text"
                placeholder="جستجوی نام سازمان یا شماره تماس…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                autocomplete="off"
            >
            <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
        </div>

        <div class="border border-gray-200 rounded overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 border-b border-gray-300">نام سازمان</th>
                        <th class="px-4 py-2 border-b border-gray-300">شماره تماس</th>
                    </tr>
                </thead>
                <tbody id="organizationTableBody">
                    @foreach($organizations as $org)
                        <tr class="cursor-pointer hover:bg-gray-50"
                            data-name="{{ $org->name }}"        {{-- بدون lowercase سمت سرور --}}
                            data-phone="{{ preg_replace('/\D+/', '', (string)($org->phone ?? '')) }}"
                            onclick="selectOrganization({{ $org->id }}, @js($org->name))">
                            <td class="px-4 py-2 border-b border-gray-200">{{ $org->name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $org->phone ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <div id="organizationNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
        </div>
    </div>
</div>


@endsection
{{-- استایل ستاره قرمز برای فیلدهای الزامی --}}
<style>
    label.required::after {
        content: ' *';
        color: red;
    }
    
    .form-field {
        @apply mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-400;
    }

    label.required::after {
        content: ' *';
        color: red;
    }
</style>



<script>
function toggleModal(modalId, open = true, focusInputId = null) {
    const el = document.getElementById(modalId);
    if (!el) return;
    if (open) {
        el.classList.remove('hidden');
        el.classList.add('flex');
        el.setAttribute('aria-hidden', 'false');
        if (focusInputId) setTimeout(() => {
            const inp = document.getElementById(focusInputId);
            if (inp) inp.focus();
        }, 10);
    } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
        el.setAttribute('aria-hidden', 'true');
    }
}

// open/close helpers
function openContactModal(){ toggleModal('contactModal', true, 'contactSearchInput'); }
function closeContactModal(){ toggleModal('contactModal', false); }
function openOrganizationModal(){ toggleModal('organizationModal', true, 'organizationSearchInput'); }
function closeOrganizationModal(){ toggleModal('organizationModal', false); }

// انتخاب مخاطب
function selectContact(id, name){
    const idEl   = document.getElementById('contact_id');
    const textEl = document.getElementById('contact_display');
    if (idEl)   idEl.value   = id ?? '';
    if (textEl) textEl.value = name ?? '';
    closeContactModal();
}

// انتخاب سازمان
function selectOrganization(id, name){
    const idEl   = document.getElementById('organization_id');
    const textEl = document.getElementById('organization_name');
    if (idEl)   idEl.value   = id ?? '';
    if (textEl) textEl.value = name ?? '';
    closeOrganizationModal();
}

// بستن با کلیک روی بک‌دراپ
document.addEventListener('click', function(e){
    ['contactModal','organizationModal'].forEach(mid => {
        const m = document.getElementById(mid);
        if (!m) return;
        if (!m.classList.contains('hidden') && e.target === m) {
            toggleModal(mid, false);
        }
    });
});

// بستن با ESC
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
        toggleModal('contactModal', false);
        toggleModal('organizationModal', false);
    }
});



</script>
<script>
// ——— ابزارهای نرمال‌سازی دقیقاً مثل مودال محصول ———

// ارقام فارسی/عربی => انگلیسی
function normalizeDigits(str) {
    if (!str) return '';
    const fa = '۰۱۲۳۴۵۶۷۸۹';
    const ar = '٠١٢٣٤٥٦٧٨٩';
    return String(str).split('').map(ch => {
        const iFa = fa.indexOf(ch);
        if (iFa > -1) return String(iFa);
        const iAr = ar.indexOf(ch);
        if (iAr > -1) return String(iAr);
        return ch;
    }).join('');
}

// حذف جداکننده‌ها/فاصله‌ها برای مقایسه عددی (شماره تلفن)
function stripSeparators(str) {
    return String(str)
        .replace(/[\u200C\u200B\u00A0\s]/g, '') // ZWNJ, ZWSP, NBSP, space
        .replace(/[,\u060C]/g, '')             // , و ،
        .replace(/[.\u066B\u066C]/g, '');      // . و جداکننده‌های عربی
}

// نرمال‌سازی ورودی جستجو
function normalizeQuery(raw) {
    const lowered = String(raw || '').toLowerCase().trim();
    const digitsFixed = normalizeDigits(lowered);
    return {
        text: digitsFixed,                      // برای نام
        numeric: stripSeparators(digitsFixed)   // برای تلفن (فقط ارقام)
    };
}

// سازندهٔ فیلتر لایو برای هر جدول
function makeLiveFilter({inputId, tbodyId, noResultId}) {
    const $input = document.getElementById(inputId);
    const $tbody = document.getElementById(tbodyId);
    const $noRes = document.getElementById(noResultId);
    if (!$input || !$tbody) return;

    let t = null; // debounce
    $input.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(applyFilter, 150);
    });

    function applyFilter() {
        const { text, numeric } = normalizeQuery($input.value);
        const rows = Array.from($tbody.querySelectorAll('tr'));

        if (!text) {
            rows.forEach(tr => tr.classList.remove('hidden'));
            if ($noRes) $noRes.classList.add('hidden');
            return;
        }

        let visible = 0;
        const isPureNumber = /^[0-9]+$/.test(numeric);

        rows.forEach(tr => {
            const name = String(tr.getAttribute('data-name') || '').toLowerCase();
            const phone = String(tr.getAttribute('data-phone') || ''); // قبلاً digits-only شده در Blade

            // منطق: اگر ورودی تماماً عدد بود ⇒ جستجو روی phone
            // وگرنه ⇒ روی name (و اگر کاربر در متن عدد هم داشت، phone هم چک می‌شود)
            const byName  = name.includes(text);
            const byPhone = isPureNumber ? phone.includes(numeric)
                                         : (numeric ? phone.includes(numeric) : false);

            const match = byName || byPhone;

            if (match) { tr.classList.remove('hidden'); visible++; }
            else { tr.classList.add('hidden'); }
        });

        if ($noRes) {
            if (visible === 0) $noRes.classList.remove('hidden');
            else $noRes.classList.add('hidden');
        }
    }
}

// فعال‌سازی فیلتر برای هر دو مودال
document.addEventListener('DOMContentLoaded', function () {
    makeLiveFilter({
        inputId: 'contactSearchInput',
        tbodyId: 'contactTableBody',
        noResultId: 'contactNoResults'
    });
    makeLiveFilter({
        inputId: 'organizationSearchInput',
        tbodyId: 'organizationTableBody',
        noResultId: 'organizationNoResults'
    });
});

// // اگر می‌خواهی با باز شدن مودال، فیلتر فوراً اعمال شود (مثلاً بعد از تایپ قبلی):
// function openContactModal(){
//     toggleModal('contactModal', true, 'contactSearchInput');
//     const i = document.getElementById('contactSearchInput');
//     if (i) i.dispatchEvent(new Event('input'));
// }
// function openOrganizationModal(){
//     toggleModal('organizationModal', true, 'organizationSearchInput');
//     const i = document.getElementById('organizationSearchInput');
//     if (i) i.dispatchEvent(new Event('input'));
// }
</script>
