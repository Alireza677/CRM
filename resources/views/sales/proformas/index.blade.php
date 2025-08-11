@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'پیش‌فاکتورها']
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                {{ __('پیش‌فاکتورها') }}
            </h2>
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-700 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-yellow-50 p-3 text-yellow-800 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Create / Import / Bulk Delete -->
            <div class="mb-4 flex flex-wrap items-center gap-2">

                {{-- دکمه ایجاد پیش‌فاکتور --}}
                <a href="{{ route('sales.proformas.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> ایجاد پیش‌فاکتور
                </a>

                {{-- دکمه رفتن به صفحه ایمپورت --}}
                <a href="{{ route('sales.proformas.import.form') }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                    <i class="fas fa-file-import mr-2"></i> ایمپورت پیش‌فاکتورها
                </a>

                {{-- دکمه حذف گروهی --}}
                    <button
                        id="bulk-delete-btn"
                        form="proformas-bulk-form"
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
                        disabled
                    >
                        <i class="fas fa-trash mr-2"></i>
                        حذف گروهی
                        <span id="selected-count-badge" class="ml-2 hidden px-2 py-0.5 text-xs rounded-full bg-white/20">
                            0
                        </span>
                    </button>

                    
            </div>

            <!-- Search and Filters -->
            <div class="mb-4">
                <form action="{{ route('sales.proformas.index') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">

                    <!-- جستجو در موضوع یا سازمان -->
                    <input type="text"
                        name="search"
                        class="border rounded px-3 py-1 w-full sm:w-64"
                        placeholder="جستجو در موضوع یا نام سازمان..."
                        value="{{ request('search') }}"
                    >

                    <!-- فیلتر سازمان -->
                    <select name="organization_id" class="border rounded px-2 py-1">
                        <option value="">همه سازمان‌ها</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- فیلتر مرحله -->
                    <select name="stage" class="border rounded px-2 py-1">
                        <option value="">همه مراحل</option>
                        @foreach(config('proforma.stages') as $key => $label)
                            <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <!-- فیلتر ارجاع به -->
                    <select name="assigned_to" class="border rounded px-2 py-1">
                        <option value="">ارجاع به</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- دکمه جستجو -->
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        جستجو
                    </button>

                    <!-- دکمه بازنشانی -->
                    <a href="{{ route('sales.proformas.index') }}" class="bg-gray-300 text-gray-800 px-3 py-2 rounded hover:bg-gray-400">
                        بازنشانی
                    </a>

                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 text-xs">
                    {{-- فرم حذف گروهی --}}
                    <form id="proformas-bulk-form"
                          action="{{ route('sales.proformas.bulk-destroy') }}"
                          method="POST"
                          onsubmit="return handleBulkDeleteSubmit(event)">
                        @csrf
                        @method('DELETE')

                        <table class="min-w-full divide-y divide-gray-200 text-[13px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">
                                        <input
                                            id="select-all"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200"
                                            title="انتخاب همه"
                                        >
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">شماره</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">موضوع</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مرحله</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">سازمان</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مخاطب</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مبلغ کل</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">تاریخ پیش‌فاکتور</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">فرصت</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">ارجاع به</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                    $stageColors = [
                                        'created' => 'bg-blue-100 text-blue-800',
                                        'accepted' => 'bg-green-100 text-green-800',
                                        'delivered' => 'bg-purple-100 text-purple-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'expired' => 'bg-gray-100 text-gray-800',
                                        'send_for_approval' => 'bg-amber-100 text-amber-800',
                                    ];
                                @endphp

                                @foreach($proformas as $proforma)
                                    @php
                                        $stageKey   = $proforma->proforma_stage ?? 'unknown';
                                        $stageClass = $stageColors[$stageKey] ?? 'bg-gray-100 text-gray-800';
                                        $stageLabel = config('proforma.stages.' . $stageKey) ?? $stageKey;

                                        $locked = ($proforma->proforma_stage === 'send_for_approval'); // قابل حذف نیست
                                    @endphp

                                    <tr>
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                class="row-check rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200"
                                                name="ids[]"
                                                value="{{ $proforma->id }}"
                                                {{ $locked ? 'disabled' : '' }}
                                                title="{{ $locked ? 'در وضعیت تایید: قابل حذف نیست' : 'انتخاب' }}"
                                            >
                                        </td>

                                        <td class="px-6 py-4 font-mono text-sm text-gray-700">
                                            {{ $proforma->proforma_number ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $proforma->subject }}
                                            </a>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stageClass }}">
                                                {{ $stageLabel }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">{{ $proforma->organization_name ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $proforma->contact_name ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ number_format($proforma->total_amount) }} تومان</td>
                                        <td class="px-6 py-4">
                                            {{ $proforma->proforma_date
                                                ? \Morilog\Jalali\Jalalian::fromCarbon(
                                                    \Carbon\Carbon::parse($proforma->proforma_date)
                                                )->format('Y/m/d')
                                                : '-' 
                                            }}  
                                        </td>
                                        <td class="px-6 py-4">{{ $proforma->opportunity->name ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $proforma->assignedTo->name ?? '-' }}</td>

                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-reverse space-x-3">
                                                <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>

                                                @if($locked)
                                                    <button onclick="showApprovalAlert()" class="text-gray-500 cursor-not-allowed" disabled>ویرایش</button>
                                                    <button onclick="showApprovalAlert()" class="text-gray-500 cursor-not-allowed" disabled>حذف</button>
                                                @else
                                                    <a href="{{ route('sales.proformas.edit', $proforma) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>

                                                    <form action="{{ route('sales.proformas.destroy', $proforma) }}" method="POST" onsubmit="return confirm('آیا از حذف این پیش‌فاکتور اطمینان دارید؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </form>

                    <div class="mt-4">
                        {{ $proformas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form       = document.getElementById('proformas-bulk-form');
    const bulkBtn    = document.getElementById('bulk-delete-btn');
    const selectAll  = document.getElementById('select-all');
    const rowChecks  = Array.from(document.querySelectorAll('.row-check'));
    const countBadge = document.getElementById('selected-count-badge');

    function refresh() {
        const selected = rowChecks.filter(c => c.checked);
        const eligible = rowChecks.filter(c => !c.disabled);

        // فعال/غیرفعال شدن دکمه
        if (bulkBtn) bulkBtn.disabled = selected.length === 0;

        // شمارنده
        if (countBadge) {
            countBadge.textContent = selected.length;
            countBadge.classList.toggle('hidden', selected.length === 0);
        }

        // حالت select-all
        if (selectAll) {
            const allChecked  = eligible.length > 0 && eligible.every(c => c.checked);
            const noneChecked = eligible.every(c => !c.checked);
            selectAll.indeterminate = !(allChecked || noneChecked);
            selectAll.checked = allChecked;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', e => {
            const v = e.target.checked;
            rowChecks.forEach(c => { if (!c.disabled) c.checked = v; });
            refresh();
        });
    }

    rowChecks.forEach(c => c.addEventListener('change', refresh));

    // هندل ارسال فرم (global)
    window.handleBulkDeleteSubmit = function (e) {
        const selected = rowChecks.filter(c => c.checked);
        if (selected.length === 0) {
            e.preventDefault();
            if (window.Swal) {
                Swal.fire({ icon: 'info', text: 'هیچ موردی انتخاب نشده است.' });
            } else {
                alert('هیچ موردی انتخاب نشده است.');
            }
            return false;
        }
        if (window.Swal) {
            e.preventDefault();
            Swal.fire({
                title: 'حذف گروهی',
                text: `آیا از حذف ${selected.length} مورد انتخابی اطمینان دارید؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف'
            }).then(res => { if (res.isConfirmed) form.submit(); });
            return false;
        }
        return true;
    };

    // مقدار اولیه
    refresh();
});
</script>

