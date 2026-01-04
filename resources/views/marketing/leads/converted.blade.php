@extends('layouts.app')

@php
    $favoriteLeadIds = $favoriteLeadIds ?? [];
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
        <div class="flex flex-col gap-3 mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">سرنخ‌های تبدیل‌شده</h2>
                <p class="text-sm text-gray-500 mt-1">همه سرنخ‌هایی که به فرصت فروش تبدیل شده‌اند در این لیست نمایش داده می‌شوند.</p>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                @include('marketing.leads.partials.listing-tabs')
            </div>
        </div>

        <form id="converted-index-form" method="GET" action="{{ route('marketing.leads.converted') }}">
            @csrf
            <input type="hidden" id="converted-form-method" value="">
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
                        ایجاد سرنخ جدید
                    </a>
                    @role('admin')
                        @include('marketing.leads.partials.export-dropdown')
                    @endrole
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <label for="converted-per-page" class="text-gray-700 whitespace-nowrap">تعداد نمایش:</label>
                    <select id="converted-per-page"
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

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-right">?????</th>
                        <th class="px-4 py-2 text-right">نام کامل</th>
                        <th class="px-4 py-2 text-right">تاریخ تبدیل</th>
                        <th class="px-4 py-2 text-right">موبایل</th>
                        <th class="px-4 py-2 text-right">منبع سرنخ</th>
                        <th class="px-4 py-2 text-right">وضعیت</th>
                        <th class="px-4 py-2 text-right">ارجاع به</th>
                        <th class="px-4 py-2 text-right">فرصت ایجاد شده</th>
                        <th class="px-4 py-2 text-center">عملیات</th>
                    </tr>
                    <tr>
                        <th class="px-4 py-2"></th>
                        <th class="px-4 py-2">
                            <input type="text" id="filter-full-name" name="full_name" value="{{ request('full_name') }}" placeholder="نام کامل"
                                   class="border rounded-md p-1 w-full text-sm">
                        </th>
                        <th class="px-4 py-2"></th>
                        <th class="px-4 py-2">
                            <input type="text" id="filter-mobile" name="mobile" value="{{ request('mobile') }}" placeholder="موبایل"
                                   class="border rounded-md p-1 w-full text-sm">
                        </th>
                        <th class="px-4 py-2">
                            <select id="filter-lead-source" name="lead_source" class="border rounded-md p-1 w-full text-sm">
                                <option value="">همه منابع</option>
                                @foreach($leadSources as $key => $label)
                                    <option value="{{ $key }}" {{ request('lead_source') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </th>
                        <th class="px-4 py-2">
                            @php $leadStatusOptions = \App\Helpers\FormOptionsHelper::leadStatuses(); @endphp
                            <select id="filter-lead-status" name="lead_status" class="border rounded-md p-1 w-full text-sm">
                                <option value="">{{ __('همه') }}</option>
                                @foreach($leadStatusOptions as $key => $label)
                                    <option value="{{ $key }}" {{ request('lead_status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="px-4 py-2">
                            <select id="filter-assigned-to" name="assigned_to" class="border rounded-md p-1 w-full text-sm">
                                <option value="">همه</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </th>
                        <th class="px-4 py-2"></th>
                        <th class="px-4 py-2 text-center"></th>
                    </tr>
                </thead>
                <tbody id="converted-tbody" class="bg-white divide-y divide-gray-200">
                    @include('marketing.leads.partials.converted-rows', ['leads' => $leads, 'favoriteLeadIds' => $favoriteLeadIds])
                </tbody>
            </table>
        </div>

        <div id="converted-pagination" class="mt-4">
            @include('marketing.leads.partials.pagination', ['leads' => $leads])
        </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const convertedForm = document.getElementById('converted-index-form');
    const methodField = document.getElementById('converted-form-method');
    const perPageSelect = document.getElementById('converted-per-page');
    const tbody = document.getElementById('converted-tbody');
    const pagination = document.getElementById('converted-pagination');
    const filterFullName = document.getElementById('filter-full-name');
    const filterMobile = document.getElementById('filter-mobile');
    const filterLeadSource = document.getElementById('filter-lead-source');
    const filterLeadStatus = document.getElementById('filter-lead-status');
    const filterAssignedTo = document.getElementById('filter-assigned-to');

    if (convertedForm && methodField) {
        convertedForm.addEventListener('submit', function (event) {
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
        const fullNameVal = (filterFullName?.value || '').trim();
        const mobileVal = (filterMobile?.value || '').trim();
        const sourceVal = filterLeadSource?.value || '';
        const statusVal = filterLeadStatus?.value || '';
        const assignedVal = filterAssignedTo?.value || '';
        const perPageVal = perPageSelect?.value || '';

        if (fullNameVal) params.set('full_name', fullNameVal); else params.delete('full_name');
        if (mobileVal) params.set('mobile', mobileVal); else params.delete('mobile');
        if (sourceVal) params.set('lead_source', sourceVal); else params.delete('lead_source');
        if (statusVal) params.set('lead_status', statusVal); else params.delete('lead_status');
        if (assignedVal) params.set('assigned_to', assignedVal); else params.delete('assigned_to');
        if (perPageVal) params.set('per_page', perPageVal); else params.delete('per_page');

        return params;
    }

    function fetchConverted(url, replaceUrl = true) {
        const reqUrl = new URL(url, window.location.origin);
        fetch(reqUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (tbody) tbody.innerHTML = data.rows || '';
                if (pagination) pagination.innerHTML = data.pagination || '';
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
        const baseUrl = convertedForm ? convertedForm.action : window.location.pathname;
        const url = baseUrl + (query ? '?' + query : '');
        fetchConverted(url, true);
    }

    let filterTimer = null;
    function scheduleFilterApply() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(applyFilters, 300);
    }

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
            fetchConverted(link.href, true);
        });
    }
});
</script>
@endsection


