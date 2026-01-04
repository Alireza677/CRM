@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [['title' => 'سازمان‌ها']];
    $sort = request('sort', 'created_at');
    $direction = request('direction', 'desc');
    $opposite = $direction === 'asc' ? 'desc' : 'asc';
@endphp

<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex gap-3 flex-wrap items-center justify-between">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">لیست سازمان‌ها</h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('sales.organizations.create') }}"
                   class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+ سازمان جدید</a>
                <a href="{{ route('sales.organizations.duplicates.index') }}"
                   class="mb-4 inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">یافتن موارد تکراری</a>
                <a href="{{ route('sales.organizations.import.form') }}"
                   class="mb-4 inline-block bg-emerald-500 text-white px-4 py-2 rounded hover:bg-emerald-600">درون‌ریزی از Excel</a>
            </div>
        </div>

        <form method="GET" action="{{ route('sales.organizations.index') }}" class="mb-4 inline-flex items-center gap-2">
            <input type="hidden" name="organization_number" value="{{ request('organization_number') }}">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="name" value="{{ request('name') }}">
            <input type="hidden" name="contact" value="{{ request('contact') }}">
            <input type="hidden" name="phone" value="{{ request('phone') }}">
            <input type="hidden" name="city" value="{{ request('city') }}">
            <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

            @php($currentPerPage = isset($perPage) ? (int)$perPage : (int)request('per_page', session('orgs_per_page', 25)))
            <label for="per_page" class="text-sm text-gray-700 whitespace-nowrap">تعداد در صفحه</label>
            <select id="per_page" name="per_page"
                    class="border rounded py-1 px-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach([10,25,50,100,250] as $size)
                    <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <form method="POST" action="{{ route('sales.organizations.bulkDelete') }}" id="bulk-delete-form" class="mb-0">
        @csrf
        @method('DELETE')

        <div class="mb-3">
            <button type="submit" onclick="return confirm('آیا از حذف گروهی مطمئن هستید؟')"
                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">حذف گروهی</button>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-right">
                    <thead id="organizations-thead" class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th class="px-3 py-2 text-gray-600">شماره</th>
                            <th class="px-3 py-2 text-gray-600">
                                <a href="{{ route('sales.organizations.index', array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => 'name', 'direction' => $sort === 'name' ? $opposite : 'asc'])) }}" class="hover:text-gray-900">
                                    نام سازمان
                                    @if ($sort === 'name')
                                        {!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}
                                    @endif
                                </a>
                            </th>
                            <th class="px-3 py-2 text-gray-600"> مخاطب مرتبط </th>
                            <th class="px-3 py-2 text-gray-600">تلفن</th>
                            <th class="px-3 py-2 text-gray-600">شهر</th>
                            <th class="px-3 py-2 text-gray-600">
                                <a href="{{ route('sales.organizations.index', array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => 'assigned_to_name', 'direction' => $sort === 'assigned_to_name' ? $opposite : 'asc'])) }}" class="hover:text-gray-900">
                                    مسئول
                                    @if ($sort === 'assigned_to_name')
                                        {!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}
                                    @endif
                                </a>
                            </th>
                            <th class="px-3 py-2 text-gray-600">اقدامات</th>
                        </tr>
                        <tr>
                            <th class="px-3 py-2"></th>
                            <th class="px-3 py-2">
                                <input type="text" id="filter-number" name="organization_number" value="{{ request('organization_number') }}"
                                       placeholder="شماره"
                                       class="w-full px-2 py-1 border rounded text-sm">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text" id="filter-name" name="name" value="{{ request('name') }}"
                                       placeholder="نام سازمان"
                                       class="w-full px-2 py-1 border rounded text-sm">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text" id="filter-contact" name="contact" value="{{ request('contact') }}"
                                       placeholder="مخاطب مرتبط"
                                       class="w-full px-2 py-1 border rounded text-sm">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text" id="filter-phone" name="phone" value="{{ request('phone') }}"
                                       placeholder="تلفن"
                                       class="w-full px-2 py-1 border rounded text-sm">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text" id="filter-city" name="city" value="{{ request('city') }}"
                                       placeholder="شهر"
                                       class="w-full px-2 py-1 border rounded text-sm">
                            </th>
                            <th class="px-3 py-2">
                                <select id="filter-assigned-to" name="assigned_to" class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">همه</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody id="organizations-tbody" class="bg-white divide-y divide-gray-200">
                        @include('sales.organizations.partials.rows', ['organizations' => $organizations])
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <div id="organizations-pagination" class="mt-4">
        @include('sales.organizations.partials.pagination', ['organizations' => $organizations])
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var sa = document.getElementById('select-all');
        if (sa) {
            sa.addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('input[name="selected[]"]');
                checkboxes.forEach(function(cb){ cb.checked = sa.checked; });
            });
        }
    })();
</script>
<script>
(function () {
    var numberInput = document.getElementById('filter-number');
    var nameInput = document.getElementById('filter-name');
    var contactInput = document.getElementById('filter-contact');
    var phoneInput = document.getElementById('filter-phone');
    var cityInput = document.getElementById('filter-city');
    var assignedSelect = document.getElementById('filter-assigned-to');
    var perPageSelect = document.getElementById('per_page');
    var tbody = document.getElementById('organizations-tbody');
    var pagination = document.getElementById('organizations-pagination');
    var thead = document.getElementById('organizations-thead');

    function buildParams() {
        var params = new URLSearchParams(window.location.search);
        var numberVal = (numberInput && numberInput.value || '').trim();
        var nameVal = (nameInput && nameInput.value || '').trim();
        var contactVal = (contactInput && contactInput.value || '').trim();
        var phoneVal = (phoneInput && phoneInput.value || '').trim();
        var cityVal = (cityInput && cityInput.value || '').trim();
        var assignedVal = assignedSelect ? assignedSelect.value : '';
        var perPageVal = perPageSelect ? perPageSelect.value : '';

        if (numberVal) params.set('organization_number', numberVal); else params.delete('organization_number');
        if (nameVal) params.set('name', nameVal); else params.delete('name');
        if (contactVal) params.set('contact', contactVal); else params.delete('contact');
        if (phoneVal) params.set('phone', phoneVal); else params.delete('phone');
        if (cityVal) params.set('city', cityVal); else params.delete('city');
        if (assignedVal) params.set('assigned_to', assignedVal); else params.delete('assigned_to');
        if (perPageVal) params.set('per_page', perPageVal); else params.delete('per_page');

        return params;
    }

    function fetchOrganizations(url, replaceUrl) {
        var reqUrl = new URL(url, window.location.origin);
        fetch(reqUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (tbody) tbody.innerHTML = data.rows || '';
                if (pagination) pagination.innerHTML = data.pagination || '';
                if (replaceUrl) {
                    history.replaceState(null, '', reqUrl.toString());
                }
            })
            .catch(function () {
                window.location.search = reqUrl.search;
            });
    }

    function applyFilters() {
        var params = buildParams();
        params.delete('page');
        var query = params.toString();
        var url = window.location.pathname + (query ? '?' + query : '');
        fetchOrganizations(url, true);
    }

    var filterTimer = null;
    function scheduleFilterApply() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(applyFilters, 300);
    }

    if (numberInput) numberInput.addEventListener('input', scheduleFilterApply);
    if (nameInput) nameInput.addEventListener('input', scheduleFilterApply);
    if (contactInput) contactInput.addEventListener('input', scheduleFilterApply);
    if (phoneInput) phoneInput.addEventListener('input', scheduleFilterApply);
    if (cityInput) cityInput.addEventListener('input', scheduleFilterApply);
    if (assignedSelect) assignedSelect.addEventListener('change', applyFilters);
    if (perPageSelect) perPageSelect.addEventListener('change', applyFilters);

    if (pagination) {
        pagination.addEventListener('click', function (e) {
            var link = e.target.closest('a');
            if (!link) return;
            e.preventDefault();
            fetchOrganizations(link.href, true);
        });
    }
    if (thead) {
        thead.addEventListener('click', function (e) {
            var link = e.target.closest('a');
            if (!link) return;
            e.preventDefault();
            fetchOrganizations(link.href, true);
        });
    }
})();
</script>
@endpush
