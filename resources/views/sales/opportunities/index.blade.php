@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش']
    ];

    // رنگ هر مرحله (پس‌زمینه ملایم + رنگ متن)
    $stageColors = [
        'open'           => 'bg-blue-100 text-blue-700',
        'proposal_sent'  => 'bg-indigo-100 text-indigo-700',
        'negotiation'    => 'bg-amber-100 text-amber-700',
        'won'            => 'bg-green-100 text-green-700',
        'lost'           => 'bg-red-100 text-red-700',
        'dead'           => 'bg-gray-200 text-gray-800',
    ];
@endphp

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex gap-3 flex-wrap items-center justify-between">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                فرصت‌های فروش
            </h2>

            <a href="{{ route('sales.opportunities.create') }}" 
            class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                + فرصت جدید
            </a>

            <a href="{{ route('sales.opportunities.import') }}" class="mb-4 inline-block bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">ایمپورت فرصت‌ها</a>

            @role('admin')
            <form id="bulk-delete-form" method="POST" action="{{ route('sales.opportunities.bulk_delete') }}" class="mb-4 inline-block"
                  onsubmit="return handleBulkDeleteSubmit(event)">
                @csrf
                @method('DELETE')
                <button id="bulk-delete-btn" type="submit"
                        class="bg-red-600 text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    حذف انتخاب‌ها
                </button>
            </form>
            @endrole
        </div>

        <form method="GET" action="{{ route('sales.opportunities.index') }}" class="mb-4 inline-flex items-center gap-2" onsubmit="return false">
            <input type="hidden" name="opportunity_number" value="{{ request('opportunity_number') }}">
            <input type="hidden" name="name" value="{{ request('name') }}">
            <input type="hidden" name="contact" value="{{ request('contact') }}">
            <input type="hidden" name="stage" value="{{ request('stage') }}">
            <input type="hidden" name="source" value="{{ request('source') }}">
            <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
            @php $currentPerPage = (int) request('per_page', 15); @endphp
            <label for="per_page" class="text-sm text-gray-700 whitespace-nowrap">تعداد در صفحه</label>
            <select id="per_page" name="per_page" class="border rounded px-2 py-1 text-sm">
                @foreach([10,15,25,50,100] as $size)
                    <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </form>
    </div>
    

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @role('admin')
                        <th class="px-2 py-2 text-center">
                            <input type="checkbox" id="select-all" class="h-4 w-4">
                        </th>
                        @endrole
                        <th class="px-2 py-2 text-right text-gray-600">شماره</th>
                        <th class="px-2 py-2 text-right text-gray-600">عنوان</th>
                        <th class="px-2 py-2 text-right text-gray-600">مخاطب</th>
                        <th class="px-2 py-2 text-right text-gray-600">مرحله فروش</th>
                        <th class="px-2 py-2 text-right text-gray-600">منبع فرصت فروش</th>
                        <th class="px-2 py-2 text-right text-gray-600">ارجاع به</th>
                        <th class="px-2 py-2 text-right text-gray-600">تاریخ ایجاد</th>
                        <th class="px-2 py-2 text-right text-gray-600">عملیات</th>
                    </tr>
                    <tr>
                        @role('admin')
                        <th class="px-2 py-1"></th>
                        @endrole
                        <th class="px-2 py-1">
                            <input type="text" id="filter-number" name="opportunity_number" value="{{ request('opportunity_number') }}"
                                class="w-full px-2 py-1 border rounded text-sm" placeholder="شماره">
                        </th>
                        <th class="px-2 py-1">
                            <input type="text" id="filter-name" name="name" value="{{ request('name') }}"
                                class="w-full px-2 py-1 border rounded text-sm" placeholder="جستجوی عنوان">
                        </th>
                        <th class="px-2 py-1">
                            <input type="text" id="filter-contact" name="contact" value="{{ request('contact') }}"
                                class="w-full px-2 py-1 border rounded text-sm" placeholder="نام مخاطب">
                        </th>
                        <th class="px-2 py-1">
                            @php $stageOptions = \App\Helpers\FormOptionsHelper::opportunityStages(); @endphp
                            <select id="filter-stage" name="stage" class="w-full px-2 py-1 border rounded text-sm">
                                <option value="">{{ __('همه') }}</option>
                                @foreach($stageOptions as $key => $label)
                                    <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="px-2 py-1">
                            <select id="filter-source" name="source" class="w-full px-2 py-1 border rounded text-sm">
                                <option value="">همه</option>
                                <option value="وب سایت" {{ request('source') == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                                <option value="مشتریان قدیمی" {{ request('source') == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                                <option value="نمایشگاه" {{ request('source') == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                                <option value="بازاریابی حضوری" {{ request('source') == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                            </select>
                        </th>
                        <th class="px-2 py-1">
                            <input type="text" id="filter-assigned-to" name="assigned_to" value="{{ request('assigned_to') }}"
                                class="w-full px-2 py-1 border rounded text-sm" placeholder="ارجاع به">
                        </th>
                        <th class="px-2 py-1 text-center"></th>
                        <th class="px-2 py-1 text-center"></th>
                    </tr>
                </thead>

                <tbody id="opportunities-tbody" class="bg-white divide-y divide-gray-200">
                    @include('sales.opportunities.partials.rows', ['opportunities' => $opportunities])
                </tbody>
            </table>
        </div>
    </div>

    <div id="opportunities-pagination" class="mt-4">
        @include('sales.opportunities.partials.pagination', ['opportunities' => $opportunities])
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateBulkDeleteState() {
        const checkboxes = Array.from(document.querySelectorAll('.row-checkbox'));
        const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.value);
        const btn = document.getElementById('bulk-delete-btn');
        if (btn) btn.disabled = selected.length === 0;
        const master = document.getElementById('select-all');
        if (master) {
            const allChecked = checkboxes.length > 0 && selected.length === checkboxes.length;
            const someChecked = selected.length > 0 && !allChecked;
            master.checked = allChecked;
            master.indeterminate = someChecked;
        }
        return selected;
    }

    function handleBulkDeleteSubmit(e) {
        const ids = updateBulkDeleteState();
        if (ids.length === 0) {
            e.preventDefault();
            return false;
        }
        if (!confirm('آیا از حذف گروهی فرصت‌های انتخاب‌شده اطمینان دارید؟')) {
            e.preventDefault();
            return false;
        }
        const form = document.getElementById('bulk-delete-form');
        // Clean previous hidden inputs
        Array.from(form.querySelectorAll('input[name="ids[]"]')).forEach(n => n.remove());
        ids.forEach(id => {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'ids[]';
            h.value = id;
            form.appendChild(h);
        });
        return true;
    }

    function bindBulkCheckboxes() {
        const master = document.getElementById('select-all');
        if (master && master.dataset.bound !== '1') {
            master.dataset.bound = '1';
            master.addEventListener('change', function () {
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = master.checked;
                });
                updateBulkDeleteState();
            });
        }
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteState);
        });
        updateBulkDeleteState();
    }

    function setupLiveFilters() {
        const nameInput = document.getElementById('filter-name');
        const numberInput = document.getElementById('filter-number');
        const contactInput = document.getElementById('filter-contact');
        const stageSelect = document.getElementById('filter-stage');
        const sourceSelect = document.getElementById('filter-source');
        const assignedInput = document.getElementById('filter-assigned-to');
        const perPageSelect = document.getElementById('per_page');
        const tbody = document.getElementById('opportunities-tbody');
        const pagination = document.getElementById('opportunities-pagination');

        function buildParams() {
            const params = new URLSearchParams(window.location.search);
            const numberVal = (numberInput?.value || '').trim();
            const nameVal = (nameInput?.value || '').trim();
            const contactVal = (contactInput?.value || '').trim();
            const stageVal = stageSelect?.value || '';
            const sourceVal = sourceSelect?.value || '';
            const assignedVal = (assignedInput?.value || '').trim();
            const perPageVal = perPageSelect?.value || '';

            if (numberVal) params.set('opportunity_number', numberVal); else params.delete('opportunity_number');
            if (nameVal) params.set('name', nameVal); else params.delete('name');
            if (contactVal) params.set('contact', contactVal); else params.delete('contact');
            if (stageVal) params.set('stage', stageVal); else params.delete('stage');
            if (sourceVal) params.set('source', sourceVal); else params.delete('source');
            if (assignedVal) params.set('assigned_to', assignedVal); else params.delete('assigned_to');
            if (perPageVal) params.set('per_page', perPageVal); else params.delete('per_page');

            return params;
        }

        function fetchOpportunities(url, replaceUrl = true) {
            const reqUrl = new URL(url, window.location.origin);
            fetch(reqUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.json())
                .then(data => {
                    if (tbody) tbody.innerHTML = data.rows || '';
                    if (pagination) pagination.innerHTML = data.pagination || '';
                    bindBulkCheckboxes();
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
            const url = window.location.pathname + (query ? '?' + query : '');
            fetchOpportunities(url, true);
        }

        let filterTimer = null;
        function scheduleFilterApply() {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(applyFilters, 300);
        }

        numberInput?.addEventListener('input', scheduleFilterApply);
        nameInput?.addEventListener('input', scheduleFilterApply);
        contactInput?.addEventListener('input', scheduleFilterApply);
        assignedInput?.addEventListener('input', scheduleFilterApply);
        stageSelect?.addEventListener('change', applyFilters);
        sourceSelect?.addEventListener('change', applyFilters);
        perPageSelect?.addEventListener('change', applyFilters);

        pagination?.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (!link) return;
            e.preventDefault();
            fetchOpportunities(link.href, true);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindBulkCheckboxes();
        setupLiveFilters();
        // Expose handler globally for inline onsubmit
        window.handleBulkDeleteSubmit = handleBulkDeleteSubmit;
    });
</script>
@endpush
