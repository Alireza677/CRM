@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش']
    ];

    // رنگ هر مرحله (پس‌زمینه ملایم + رنگ متن)
    $stageColors = [
        'جدید'            => 'bg-blue-100 text-blue-700',
        'در حال پیگیری'   => 'bg-yellow-100 text-yellow-700',
        'پیگیری در آینده' => 'bg-orange-100 text-orange-700',
        'برنده'           => 'bg-green-100 text-green-700',   // ✅ سبز
        'بازنده'          => 'bg-gray-100 text-gray-700',
        'سرکاری'          => 'bg-red-100 text-red-700',
        'ارسال پیش فاکتور'=> 'bg-purple-100 text-purple-700',
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

        <form method="GET" action="{{ route('sales.opportunities.index') }}" class="mb-4 inline-flex items-center gap-2">
            <input type="hidden" name="name" value="{{ request('name') }}">
            <input type="hidden" name="contact" value="{{ request('contact') }}">
            <input type="hidden" name="stage" value="{{ request('stage') }}">
            <input type="hidden" name="source" value="{{ request('source') }}">
            <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
            @php $currentPerPage = (int) request('per_page', 15); @endphp
            <label for="per_page" class="text-sm text-gray-700 whitespace-nowrap">تعداد در صفحه</label>
            <select id="per_page" name="per_page" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
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
                        <th class="px-2 py-2 text-right text-gray-600">عنوان</th>
                        <th class="px-2 py-2 text-right text-gray-600">مخاطب</th>
                        <th class="px-2 py-2 text-right text-gray-600">مرحله فروش</th>
                        <th class="px-2 py-2 text-right text-gray-600">منبع فرصت فروش</th>
                        <th class="px-2 py-2 text-right text-gray-600">ارجاع به</th>
                        <th class="px-2 py-2 text-right text-gray-600">تاریخ ایجاد</th>
                        <th class="px-2 py-2 text-right text-gray-600">عملیات</th>
                    </tr>
                    <tr>
                        <form method="GET" action="{{ route('sales.opportunities.index') }}">
                            @role('admin')
                            <th class="px-2 py-1"></th>
                            @endrole
                            <th class="px-2 py-1">
                                <input type="text" name="name" value="{{ request('name') }}"
                                    class="w-full px-2 py-1 border rounded text-sm" placeholder="جستجوی عنوان">
                            </th>
                            <th class="px-2 py-1">
                                <input type="text" name="contact" value="{{ request('contact') }}"
                                    class="w-full px-2 py-1 border rounded text-sm" placeholder="نام مخاطب">
                            </th>
                            <th class="px-2 py-1">
                                <select name="stage" class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">همه</option>
                                    <option value="در حال پیگیری" {{ request('stage') == 'در حال پیگیری' ? 'selected' : '' }}>در حال پیگیری</option>
                                    <option value="پیگیری در آینده" {{ request('stage') == 'پیگیری در آینده' ? 'selected' : '' }}>پیگیری در آینده</option>
                                    <option value="برنده" {{ request('stage') == 'برنده' ? 'selected' : '' }}>برنده</option>
                                    <option value="بازنده" {{ request('stage') == 'بازنده' ? 'selected' : '' }}>بازنده</option>
                                    <option value="سرکاری" {{ request('stage') == 'سرکاری' ? 'selected' : '' }}>سرکاری</option>
                                    <option value="ارسال پیش فاکتور" {{ request('stage') == 'ارسال پیش فاکتور' ? 'selected' : '' }}>ارسال پیش فاکتور</option>
                                </select>
                            </th>
                            <th class="px-2 py-1">
                                <select name="source" class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">همه</option>
                                    <option value="وب سایت" {{ request('source') == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                                    <option value="مشتریان قدیمی" {{ request('source') == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                                    <option value="نمایشگاه" {{ request('source') == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                                    <option value="بازاریابی حضوری" {{ request('source') == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                                </select>
                            </th>
                            <th class="px-2 py-1">
                                <input type="text" name="assigned_to" value="{{ request('assigned_to') }}"
                                    class="w-full px-2 py-1 border rounded text-sm" placeholder="ارجاع به">
                            </th>
                            <th class="px-2 py-1 text-center">
                                <button type="submit" class="text-sm text-blue-600 hover:underline">جستجو</button>
                            </th>
                            <th class="px-2 py-1 text-center">
                                <a href="{{ route('sales.opportunities.index') }}" class="text-sm text-gray-500 hover:text-red-500">
                                    پاک‌سازی
                                </a>
                            </th>
                        </form>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($opportunities as $opportunity)
                        <tr>
                            @role('admin')
                            <td class="px-3 py-4 text-center">
                                <input type="checkbox" class="row-checkbox h-4 w-4" value="{{ $opportunity->id }}">
                            </td>
                            @endrole
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('sales.opportunities.show', $opportunity) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $opportunity->name ?? '-' }}
                                </a>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $opportunity->contact->name ?? '—' }}
                            </td>

                            {{-- ⭐ مرحله فروش به صورت بادج رنگی --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $stage = $opportunity->stage ?? '—';
                                    $badgeClass = $stageColors[$stage] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badgeClass }}">
                                    {{ $stage }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $opportunity->source ?? '—' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $opportunity->assignedTo->name ?? '—' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ jdate($opportunity->created_at) }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-4">
                                    <a href="{{ route('sales.opportunities.edit', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                    @role('admin')
                                    <form action="{{ route('sales.opportunities.destroy', $opportunity) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('آیا از حذف این فرصت فروش اطمینان دارید؟')">
                                            حذف
                                        </button>
                                    </form>
                                    @endrole
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin') ? 8 : 7 }}" class="px-6 py-4 text-center text-gray-400">
                                هیچ فرصتی یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $opportunities->links() }}
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

    document.addEventListener('DOMContentLoaded', function () {
        const master = document.getElementById('select-all');
        if (master) {
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
        // Expose handler globally for inline onsubmit
        window.handleBulkDeleteSubmit = handleBulkDeleteSubmit;
    });
</script>
@endpush
