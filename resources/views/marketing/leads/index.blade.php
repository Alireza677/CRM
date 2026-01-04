@extends('layouts.app')

@php
    $favoriteLeadIds = $favoriteLeadIds ?? [];
    $leadListingRoute = $leadListingRoute ?? 'marketing.leads.index';
    $isJunkListing = $isJunkListing ?? request()->routeIs('sales.leads.junk');
    $leadPoolRules = $leadPoolRules ?? [];
    $leadPoolFirstActivity = $leadPoolRules['first_activity_deadline_label'] ?? '24 ساعت';
    $leadPoolMaxReassignments = $leadPoolRules['max_reassignments'] ?? 3;
    $leadPoolFinalDecisionDays = $leadPoolRules['final_decision_days'] ?? 14;
    $pageTitle = $isJunkListing ? 'سرکاری‌ها' : 'سرنخ‌های فروش';
    $pageSubtitle = $isJunkListing
        ? 'لیست سرنخ‌هایی که در وضعیت سرکاری قرار دارند.'
        : 'لیست سرنخ های فعال که در حال پیگیری هستند.';
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
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">{{ $pageTitle }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $pageSubtitle }}</p>
                </div>
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
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            @include('marketing.leads.partials.listing-tabs')
        </div>

        <form id="leads-index-form" method="GET" action="{{ route($leadListingRoute) }}">
            @csrf
            <input type="hidden" id="leads-form-method" value="">
            @foreach(request()->except('per_page', 'page', 'full_name', 'mobile', 'lead_source', 'lead_status', 'assigned_to') as $name => $value)
                @if(is_array($value))
                    @foreach($value as $item)
                        <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('marketing.leads.create') }}"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md shadow hover:bg-blue-700">
                            <i class="fas fa-plus ml-1 text-sm"></i>
                            ایجاد سرنخ
                        </a>
                    @role('admin')
                        @include('marketing.leads.partials.export-dropdown')
                    @endrole
                    @role('admin')
                        <button
                            type="submit"
                            id="bulk-delete-btn"
                            formmethod="POST"
                            formaction="{{ route('marketing.leads.bulk-delete') }}"
                            onclick="return confirm('آیا مطمئنید؟')"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
                            disabled>
                            <i class="fas fa-trash ml-2"></i>
                            حذف انتخاب‌شده‌ها
                            <span id="selected-count-badge"
                                class="ml-2 hidden px-2 py-0.5 text-xs rounded-full bg-white/20">0</span>
                        </button>
                    @endrole
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <label for="per-page-select" class="text-gray-700 whitespace-nowrap">تعداد نمایش:</label>
                    <select id="per-page-select"
                            name="per_page"
                            class="border rounded-md px-2 py-1 focus:outline-none focus:ring">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- جدول -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-center">
                                <input type="checkbox" id="select-all" class="form-checkbox">
                            </th>
                            <th class="px-2 py-2 text-right">شماره</th>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">تاریخ ایجاد</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                        <tr>
                            <th class="px-2 py-2"></th>
                            <th class="px-2 py-2">
                                <input type="text" id="filter-lead-number" name="lead_number" value="{{ request('lead_number') }}" placeholder="شماره"
                                       class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th class="px-2 py-2">
                                <input type="text" id="filter-full-name" name="full_name" value="{{ request('full_name') }}" placeholder="نام کامل"
                                       class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th class="px-2 py-2"></th>
                            <th class="px-2 py-2">
                                <input type="text" id="filter-mobile" name="mobile" value="{{ request('mobile') }}" placeholder="موبایل"
                                       class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th class="px-2 py-2">
                                <select id="filter-lead-source" name="lead_source" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه منابع</option>
                                    @foreach($leadSources as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_source') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-2 py-2">
                                @php $leadStatusOptions = \App\Helpers\FormOptionsHelper::leadStatuses(); @endphp
                                <select id="filter-lead-status" name="lead_status" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">{{ __('همه') }}</option>
                                    @foreach($leadStatusOptions as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-2 py-2">
                                <select id="filter-assigned-to" name="assigned_to" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-2 py-2 text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="leads-tbody" class="bg-white divide-y divide-gray-200">
                        @include('marketing.leads.partials.rows', ['leads' => $leads, 'favoriteLeadIds' => $favoriteLeadIds])
                    </tbody>
                </table>
            </div>


            <div id="leads-pagination" class="mt-4">
                @include('marketing.leads.partials.pagination', ['leads' => $leads])
            </div>
        </form>
    </div>
</div>

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
    const leadsForm = document.getElementById('leads-index-form');
    const methodField = document.getElementById('leads-form-method');
    const perPageSelect = document.getElementById('per-page-select');
    const rulesTrigger = document.getElementById('lead-rules-trigger');
    const rulesModal = document.getElementById('lead-rules-modal');
    const rulesBackdrop = document.getElementById('lead-rules-backdrop');
    const rulesCloseButtons = document.querySelectorAll('[data-lead-rules-close]');
    const tbody = document.getElementById('leads-tbody');
    const pagination = document.getElementById('leads-pagination');
    const filterLeadNumber = document.getElementById('filter-lead-number');
    const filterFullName = document.getElementById('filter-full-name');
    const filterMobile = document.getElementById('filter-mobile');
    const filterLeadSource = document.getElementById('filter-lead-source');
    const filterLeadStatus = document.getElementById('filter-lead-status');
    const filterAssignedTo = document.getElementById('filter-assigned-to');

    function refreshBulkState() {
        const boxes = rowBoxes();
        const count = boxes.filter(b => b.checked).length;

        if (bulkBtn) bulkBtn.disabled = (count === 0);

        if (countBadge) {
            countBadge.textContent = count;
            countBadge.classList.toggle('hidden', count === 0);
        }
    }

    function bindRowCheckboxes() {
        rowBoxes().forEach(cb => {
            if (cb.dataset.bound === '1') return;
            cb.dataset.bound = '1';
            cb.addEventListener('change', refreshBulkState);
        });
    }

    // انتخاب همه
    if (selectAll && selectAll.dataset.bound !== '1') {
        selectAll.dataset.bound = '1';
        selectAll.addEventListener('change', function () {
            rowBoxes().forEach(cb => cb.checked = this.checked);
            refreshBulkState();
        });
    }

    if (leadsForm && methodField) {
        leadsForm.addEventListener('submit', function (event) {
            const submitter = event.submitter;
            if (!submitter) {
                methodField.value = '';
                methodField.name = '';
                return;
            }

            const method = submitter.getAttribute('data-method');
            if (method) {
                methodField.name = '_method';
                methodField.value = method;
            } else {
                methodField.name = '';
                methodField.value = '';
            }
        });
    }

    function buildParams() {
        const params = new URLSearchParams(window.location.search);
        const leadNumberVal = (filterLeadNumber?.value || '').trim();
        const fullNameVal = (filterFullName?.value || '').trim();
        const mobileVal = (filterMobile?.value || '').trim();
        const sourceVal = filterLeadSource?.value || '';
        const statusVal = filterLeadStatus?.value || '';
        const assignedVal = filterAssignedTo?.value || '';
        const perPageVal = perPageSelect?.value || '';

        if (leadNumberVal) params.set('lead_number', leadNumberVal); else params.delete('lead_number');
        if (fullNameVal) params.set('full_name', fullNameVal); else params.delete('full_name');
        if (mobileVal) params.set('mobile', mobileVal); else params.delete('mobile');
        if (sourceVal) params.set('lead_source', sourceVal); else params.delete('lead_source');
        if (statusVal) params.set('lead_status', statusVal); else params.delete('lead_status');
        if (assignedVal) params.set('assigned_to', assignedVal); else params.delete('assigned_to');
        if (perPageVal) params.set('per_page', perPageVal); else params.delete('per_page');

        return params;
    }

    function fetchLeads(url, replaceUrl = true) {
        const reqUrl = new URL(url, window.location.origin);
        fetch(reqUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (tbody) tbody.innerHTML = data.rows || '';
                if (pagination) pagination.innerHTML = data.pagination || '';
                bindRowCheckboxes();
                refreshBulkState();
                if (replaceUrl) {
                    history.replaceState(null, '', reqUrl.toString());
                }
            })
            .catch(() => {
                window.location.search = reqUrl.search;
            });
    }

    function applyFilters() {
        const params = buildParams();
        params.delete('page');
        const query = params.toString();
        const baseUrl = leadsForm ? leadsForm.action : window.location.pathname;
        const url = baseUrl + (query ? '?' + query : '');
        fetchLeads(url, true);
    }

    let filterTimer = null;
    function scheduleFilterApply() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(applyFilters, 300);
    }

    filterLeadNumber?.addEventListener('input', scheduleFilterApply);
    filterFullName?.addEventListener('input', scheduleFilterApply);
    filterMobile?.addEventListener('input', scheduleFilterApply);
    filterLeadSource?.addEventListener('change', applyFilters);
    filterLeadStatus?.addEventListener('change', applyFilters);
    filterAssignedTo?.addEventListener('change', applyFilters);

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            if (methodField) {
                methodField.value = '';
                methodField.name = '';
            }
            applyFilters();
        });
    }

    if (pagination) {
        pagination.addEventListener('click', function (event) {
            const link = event.target.closest('a');
            if (!link) return;
            event.preventDefault();
            fetchLeads(link.href, true);
        });
    }

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
    bindRowCheckboxes();
    refreshBulkState();
});
</script>



@endsection
