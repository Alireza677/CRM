@extends('layouts.app')

@php
    $favoriteLeadIds = $favoriteLeadIds ?? [];
    $leadListingRoute = $leadListingRoute ?? 'marketing.leads.index';
    $isJunkListing = $isJunkListing ?? request()->routeIs('sales.leads.junk');
    $leadPoolRules = $leadPoolRules ?? [];
    $leadPoolFirstActivity = $leadPoolRules['first_activity_deadline_label'] ?? '24 ساعت';
    $leadPoolMaxReassignments = $leadPoolRules['max_reassignments'] ?? 3;
    $leadPoolFinalDecisionDays = $leadPoolRules['final_decision_days'] ?? 14;
@endphp

@section('content')
@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

<div>
    <div class="px-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">سرنخ‌های فروش</h2>
            <div class="relative group">
                <button
                    type="button"
                    id="lead-rules-trigger"
                    class="w-9 h-9 inline-flex items-center justify-center rounded-full bg-white border border-gray-200 shadow-sm text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    aria-label="قوانین سرنخ‌ها"
                >
                    <i class="fas fa-exclamation-circle text-lg"></i>
                </button>
                <div class="absolute right-0 mt-2 w-44 px-3 py-2 bg-gray-800 text-white text-xs rounded-md shadow-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition pointer-events-none">
                    قوانین استخر سرنخ‌ها
                </div>
            </div>
        </div>

        <!-- فرم جستجو -->
        <form method="GET" action="{{ route($leadListingRoute) }}" class="mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <!-- ردیف عنوان -->
                        <tr>
                            <th></th>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                        <!-- ردیف فیلتر -->
                        <tr>
                            <th></th>
                            <th>
                                <input type="text" name="full_name" value="{{ request('full_name') }}" placeholder="نام کامل"
                                    class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th>
                                <input type="text" name="mobile" value="{{ request('mobile') }}" placeholder="موبایل"
                                       class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th>
                                <select name="lead_source" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه منابع</option>
                                    @foreach($leadSources as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_source') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th>
                                @php $leadStatusOptions = \App\Helpers\FormOptionsHelper::leadStatuses(); @endphp
                                <select name="lead_status" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">{{ __('همه') }}</option>
                                    @foreach($leadStatusOptions as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th>
                                <select name="assigned_to" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="text-center">
                                <div class="flex gap-2 justify-center">
                                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">جستجو</button>
                                    <a href="{{ route($leadListingRoute) }}" class="bg-gray-300 px-3 py-1 rounded text-sm">پاکسازی</a>
                                </div>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </form>

        <!-- فرم حذف گروهی + جدول -->
        <div class="flex justify-start items-center mb-4">
            <div class="flex gap-2">
                {{-- دکمه ایجاد سرنخ --}}
                <a href="{{ route('marketing.leads.create') }}"
                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md shadow hover:bg-blue-700">
                        <i class="fas fa-plus ml-1 text-sm"></i>
                        ایجاد سرنخ
                    </a>

                <a href="{{ route('marketing.leads.favorites.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-amber-500 text-white rounded-md shadow hover:bg-amber-600">
                    <i class="fas fa-star ml-1 text-sm"></i>
                    علاقه‌مندی‌ها
                </a>
                <a href="{{ route('sales.leads.junk') }}"
                    class="inline-flex items-center px-4 py-2 rounded-md shadow {{ $isJunkListing ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                    <i class="fas fa-ban ml-1 text-sm"></i>
                    سرکاری‌ها
                </a>
                <a href="{{ route('marketing.leads.converted') }}"
                    class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-md shadow hover:bg-emerald-600">
                    <i class="fas fa-sync ml-1 text-sm"></i>
                    سرنخ‌های تبدیل‌شده
                </a>
                {{-- دکمه حذف انتخاب‌شده‌ها: فقط برای ادمین --}}
                @role('admin')
                    <button type="submit"
                            form="leads-bulk-form"
                            id="bulk-delete-btn"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
                            disabled>
                        <i class="fas fa-trash ml-2"></i>
                        حذف انتخاب‌شده‌ها
                        <span id="selected-count-badge"
                            class="ml-2 hidden px-2 py-0.5 text-xs rounded-full bg-white/20">0</span>
                    </button>
                @endrole
                @role('admin')
                    <a href="{{ route('sales.contacts.export.format', ['format' => 'csv']) }}"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-xs font-semibold">
                        <i class="fas fa-file-csv ml-1 text-sm"></i> اکسپورت (CSV)
                    </a>

                    <a href="{{ route('sales.contacts.export.format', ['format' => 'xlsx']) }}"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-xs font-semibold">
                        <i class="fas fa-file-excel ml-1 text-sm"></i> اکسپورت (XLSX)
                    </a>
                @endrole

            </div>
            <form method="GET" action="{{ route($leadListingRoute) }}" class="flex items-center gap-2 text-sm">
                <label for="per-page-select" class="text-gray-700 whitespace-nowrap">تعداد نمایش:</label>
                <select id="per-page-select"
                        name="per_page"
                        class="border rounded-md px-2 py-1 focus:outline-none focus:ring"
                        onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
                @foreach(request()->except('per_page', 'page') as $name => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
            </form>
        </div>

        <form id="leads-bulk-form" method="POST" action="{{ route('marketing.leads.bulk-delete') }}" onsubmit="return confirm('آیا مطمئنید؟')">
            @csrf

            <!-- جدول -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-center">
                                <input type="checkbox" id="select-all" class="form-checkbox">
                            </th>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">تاریخ ایجاد</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
@forelse($leads as $lead)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-2 py-2 text-center">
                                <input type="checkbox" name="selected_leads[]" value="{{ $lead->id }}" class="form-checkbox row-checkbox">
                            </td>
                            <td class="px-6 py-2 text-sm">
                                @php
                                    $showReengagedBadge = (bool) $lead->is_reengaged;
                                    $isWebsiteSource = $lead->lead_source === 'website';
                                @endphp
                                <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-700 hover:underline">
                                    {{ $lead->full_name }}
                                </a>
                                @if($showReengagedBadge)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $isWebsiteSource ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                                        بازگشتی از وب‌سایت
                                    </span>
                                @endif
                                @if(!empty($lead->converted_at))
                                    <span class="ml-2 px-2 py-0.5 text-[10px] rounded-full bg-green-100 text-green-800 align-middle">تبدیل شده</span>
                                @endif
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500">
                                {{ \Morilog\Jalali\Jalalian::forge($lead->created_at)->format('Y/m/d') }}
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500">{{ $lead->mobile ?? $lead->phone }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500">
                                {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
                            </td>
                            <td class="px-6 py-2">
                                @php
                                    $leadStatusColors = [
                                        'new' => 'bg-blue-100 text-blue-800',
                                        'contacted' => 'bg-yellow-100 text-yellow-800',
                                        'converted_to_opportunity' => 'bg-emerald-100 text-emerald-800',
                                        'converted' => 'bg-emerald-100 text-emerald-800',
                                        'discarded' => 'bg-red-100 text-red-800',
                                        'junk' => 'bg-red-100 text-red-800',
                                    ];
                                     $rawStatus = !empty($lead->lead_status) ? $lead->lead_status : $lead->status;
                                    $statusKey = \App\Models\SalesLead::normalizeStatus($rawStatus) ?? $rawStatus;
                                    $badgeClass = $leadStatusColors[$statusKey] ?? 'bg-gray-200 text-gray-800';
                                @endphp
                                <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $badgeClass }}">
                                    {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($statusKey) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500">
                                @if($lead->assignedUser)
                                    {{ $lead->assignedUser->name }}
                                @elseif($lead->assigned_to)
                                    (کاربر حذف شده) [ID: {{ $lead->assigned_to }}]
                                @else
                                    بدون مسئول
                                @endif
                            </td>
                            <td class="px-6 py-2 text-center">
                                <div class="flex items-center gap-3 justify-center">
                                    @php
                                        $isFavorite = in_array($lead->id, $favoriteLeadIds);
                                        $favoriteFormId = 'favorite-toggle-' . $lead->id;
                                    @endphp
                                    <button
                                        type="submit"
                                        form="{{ $favoriteFormId }}"
                                        class="inline-flex items-center text-xs px-2 py-1 rounded {{ $isFavorite ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                                        aria-label="{{ $isFavorite ? 'حذف از علاقه‌مندی' : 'افزودن به علاقه‌مندی' }}">
                                        <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-star ml-1"></i>
                                    </button>
                                    <a href="{{ route('marketing.leads.edit', $lead) }}" class="text-blue-500 hover:underline">ویرایش</a>
                                    @if(empty($lead->converted_at))
                                        <button
                                            type="submit"
                                            class="text-sm px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700"
                                            form="lead-convert-form"
                                            formaction="{{ route('marketing.leads.convert', $lead) }}"
                                            onclick="return confirm('این سرنخ به فرصت فروش تبدیل شود؟');"
                                        >
                                            تبدیل به فرصت
                                        </button>
                                    @else
                                        <span class="text-green-700 text-xs">تبدیل شده</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">هیچ سرنخی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>


            <div class="mt-4">
                {{ $leads->links() }}
            </div>
        </form>
    </div>
</div>

@foreach($leads as $lead)
    @php
        $isFavorite = in_array($lead->id, $favoriteLeadIds);
    @endphp
    <form id="favorite-toggle-{{ $lead->id }}" method="POST" action="{{ $isFavorite ? route('marketing.leads.favorites.destroy', $lead) : route('marketing.leads.favorites.store', $lead) }}" style="display:none">
        @csrf
        @if($isFavorite)
            @method('DELETE')
        @endif
    </form>
@endforeach

<!-- Standalone form for conversion (outside bulk-delete form to avoid nested forms) -->
<form id="lead-convert-form" method="POST" style="display:none">
    @csrf
</form>

<!-- Lead rules modal -->
<div id="lead-rules-modal" class="fixed inset-0 z-40 hidden items-center justify-center px-4">
    <div id="lead-rules-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-lead-rules-close></div>
    <div class="relative bg-white rounded-lg shadow-xl border border-gray-200 max-w-2xl w-full mx-auto p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-gray-500 mb-1">راهنمای استخر سرنخ‌ها</p>
                <h3 class="text-lg font-semibold text-gray-800">قوانین سرنخ‌ها</h3>
            </div>
            <button type="button" class="text-gray-500 hover:text-gray-700 transition" aria-label="بستن" data-lead-rules-close>
                <span class="text-xl leading-none">&times;</span>
            </button>
        </div>
        <div class="space-y-3 text-sm text-gray-700 leading-6">
            <ul class="list-disc list-inside space-y-2 pr-2">
                <li>Round-robin assignment: سرنخ جدید نوبتی بین مسئول‌ها تقسیم می‌شود.</li>
                <li>مهلت اولین فعالیت: کاربر تا {{ $leadPoolFirstActivity }} فرصت دارد فعالیت معتبر ثبت کند؛ در غیر این صورت ارجاع به نفر بعد.</li>
                <li>فعالیت معتبر: ثبت تماس/پیگیری، ثبت یادداشت، یا هر Activity ثبت‌شده.</li>
                <li>سقف ارجاع: تا {{ $leadPoolMaxReassignments }} بار قابل جابه‌جایی است.</li>
                <li>تعیین تکلیف نهایی: نهایتاً تا {{ $leadPoolFinalDecisionDays }} روز باید یا تبدیل به فرصت شود یا برود به «سرکاری‌ها».</li>
                <li>سرنخ‌های دستی هم مشمول همین قوانین هستند.</li>
                <li>اعلان‌ها: با هر تغییر مسئول، به مسئول جدید نوتیفیکیشن ارسال می‌شود.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkBtn   = document.getElementById('bulk-delete-btn');
    const countBadge= document.getElementById('selected-count-badge');
    const selectAll = document.getElementById('select-all');
    const rowBoxes  = () => Array.from(document.querySelectorAll('.row-checkbox'));
    const rulesTrigger = document.getElementById('lead-rules-trigger');
    const rulesModal = document.getElementById('lead-rules-modal');
    const rulesBackdrop = document.getElementById('lead-rules-backdrop');
    const rulesCloseButtons = document.querySelectorAll('[data-lead-rules-close]');

    function refreshBulkState() {
        const boxes = rowBoxes();
        const count = boxes.filter(b => b.checked).length;

        if (bulkBtn) bulkBtn.disabled = (count === 0);

        if (countBadge) {
            countBadge.textContent = count;
            countBadge.classList.toggle('hidden', count === 0);
        }
    }

    // انتخاب همه
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowBoxes().forEach(cb => cb.checked = this.checked);
            refreshBulkState();
        });
    }

    // هر ردیف
    rowBoxes().forEach(cb => cb.addEventListener('change', refreshBulkState));

    // Lead rules modal interactions
    const openRulesModal = () => {
        if (!rulesModal) return;
        rulesModal.classList.remove('hidden');
        rulesModal.classList.add('flex');
    };

    const closeRulesModal = () => {
        if (!rulesModal) return;
        rulesModal.classList.add('hidden');
        rulesModal.classList.remove('flex');
    };

    if (rulesTrigger && rulesModal) {
        rulesTrigger.addEventListener('click', function (event) {
            event.preventDefault();
            openRulesModal();
        });
    }

    rulesCloseButtons.forEach(btn => btn.addEventListener('click', closeRulesModal));

    if (rulesBackdrop) {
        rulesBackdrop.addEventListener('click', closeRulesModal);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && rulesModal && !rulesModal.classList.contains('hidden')) {
            closeRulesModal();
        }
    });

    // بار اول
    refreshBulkState();
});
</script>



@endsection
