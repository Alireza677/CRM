@extends('layouts.app')

@section('content')
@php
    use Morilog\Jalali\Jalalian;
    $canBulkDeletePurchaseOrders = auth()->check() && auth()->user()->can('purchase_orders.delete.own');
    $perPage = (int) ($perPage ?? request('per_page', 25));
    $perPageOptions = [25, 50, 100];
    $statusLabels = \App\Models\PurchaseOrder::statuses();
@endphp

<div class="px-6 h-[calc(100vh-140px)] flex flex-col gap-4 overflow-hidden" dir="rtl">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-right">
            <h1 class="text-lg font-bold text-gray-800">سفارش‌های خرید</h1>
            <div class="text-xs text-gray-500">{{ $purchaseOrders->total() }} مورد</div>
        </div>
        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('inventory.purchase-orders.create') }}" class="inline-flex items-center justify-center h-9 px-4 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">سفارش خرید جدید</a>
            @if ($canBulkDeletePurchaseOrders)
                <button
                    type="submit"
                    id="bulk-delete-button"
                    form="bulk-delete-form"
                    onclick="return confirm('سفارش‌های انتخاب‌شده حذف شوند؟')"
                    class="inline-flex items-center h-9 px-4 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
                    disabled>
                    حذف انتخاب‌شده‌ها
                </button>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <form id="filters-form" method="GET" action="{{ route('inventory.purchase-orders.index') }}">
                <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
            </form>
            <form id="per-page-form" method="GET" action="{{ route('inventory.purchase-orders.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
                <label class="text-xs text-gray-500">تعداد در صفحه</label>
                <select name="per_page" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs">
                    @foreach($perPageOptions as $size)
                        <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
            <div class="text-xs text-gray-500">صفحه {{ $purchaseOrders->currentPage() }} از {{ $purchaseOrders->lastPage() }}</div>
        </div>
    </div>

    <div class="flex-1 min-h-0 overflow-auto">
        <form id="bulk-delete-form" action="{{ route('inventory.purchase-orders.bulk-destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full text-right text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500">
                        <tr>
                            @if ($canBulkDeletePurchaseOrders)
                                <th class="px-3 py-2 sticky top-0 z-20 bg-gray-50">
                                    <input type="checkbox" id="purchase-orders-select-all" class="rounded border-gray-300" />
                                </th>
                            @endif
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">شماره</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">عنوان</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">نوع</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">تأمین‌کننده</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">تاریخ درخواست</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">نیاز تا</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">وضعیت</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">جمع کل</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">پرداخت‌شده</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">مانده</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">درخواست‌کننده</th>
                            <th class="px-3 py-2 font-medium sticky top-0 z-20 bg-gray-50">اقدامات</th>
                        </tr>
                        <tr class="bg-white text-xs text-gray-500">
                            @if ($canBulkDeletePurchaseOrders)
                                <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            @endif
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white">
                                <input type="text" name="subject" value="{{ request('subject') }}" form="filters-form" placeholder="عنوان" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs" />
                            </th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white">
                                <input type="text" name="supplier" value="{{ request('supplier') }}" form="filters-form" placeholder="تأمین‌کننده" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs" />
                            </th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white">
                                <input type="hidden" id="purchase_date" name="purchase_date" value="{{ request('purchase_date') }}" form="filters-form" />
                                <input type="text" id="purchase_date_shamsi" value="" form="filters-form" placeholder="تاریخ" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs persian-datepicker" data-alt-field="purchase_date" data-skip-autofill="1" />
                            </th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white">
                                <select name="status" form="filters-form" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs">
                                    <option value="">همه</option>
                                    @foreach($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white">
                                <input type="text" name="requested_by" value="{{ request('requested_by') }}" form="filters-form" placeholder="درخواست‌کننده" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs" />
                            </th>
                            <th class="px-3 py-2 sticky top-8 z-10 bg-white">
                                <div class="flex items-center gap-2">
                                    <button type="submit" form="filters-form" class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-600 hover:bg-gray-100">اعمال</button>
                                    <button type="button" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 hover:bg-gray-100" onclick="const f=document.getElementById('filters-form'); if(f){ window.location = f.action; }">ریست</button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 px-2">
                        @forelse($purchaseOrders as $order)
                            <tr class="hover:bg-gray-50 js-po-row" data-order-id="{{ $order->id }}">
                                @if ($canBulkDeletePurchaseOrders)
                                    <td class="px-3 py-2">
                                        @can('delete', $order)
                                            <input type="checkbox" name="selected_orders[]" value="{{ $order->id }}" class="rounded border-gray-300 js-po-row-checkbox" />
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endcan
                                    </td>
                                @endif
                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">
                                    {{ $order->po_number ?? ('#'.$order->id) }}
                                </td>
                                <td class="px-3 py-2 text-blue-700">
                                    <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="hover:underline">
                                        {{ $order->subject }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-gray-700">{{ $order->purchase_type === 'unofficial' ? 'غیررسمی' : 'رسمی' }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $order->supplier_name }}</td>
                                <td class="px-3 py-2 text-gray-700">
                                    {{ $order->request_date ? Jalalian::fromCarbon($order->request_date)->format('Y/m/d') : '—' }}
                                </td>
                                <td class="px-3 py-2 text-gray-700">
                                    {{ $order->needed_by_date ? Jalalian::fromCarbon($order->needed_by_date)->format('Y/m/d') : '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    @php
                                        $statusBadgeMap = [
                                          'created' => 'bg-blue-100 text-blue-800',
                                          'supervisor_approval' => 'bg-amber-100 text-amber-800',
                                          'manager_approval' => 'bg-yellow-100 text-yellow-800',
                                          'accounting_approval' => 'bg-teal-100 text-teal-800',
                                          'purchasing' => 'bg-indigo-100 text-indigo-800',
                                          'purchased' => 'bg-green-100 text-green-800',
                                          'warehouse_delivered' => 'bg-green-100 text-green-800',
                                          'paid' => 'bg-green-100 text-green-800',
                                          'rejected' => 'bg-red-100 text-red-800',
                                        ];
                                        $currentStatus = $order->status;
                                        $statusText = $statusLabels[$currentStatus] ?? 'نامشخص';
                                        $statusClass = $statusBadgeMap[$currentStatus] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-700">{{ number_format($order->total_amount, 0) }} ریال</td>
                                <td class="px-3 py-2 text-gray-700">{{ number_format($order->previously_paid_amount ?? 0, 0) }} ریال</td>
                                <td class="px-3 py-2 text-gray-700">{{ number_format($order->remaining_payable_amount ?? 0, 0) }} ریال</td>
                                <td class="px-3 py-2 text-gray-700">{{ $order->requested_by_name }}</td>
                                <td class="px-3 py-2 text-gray-700">
                                    @can('delete', $order)
                                        <form action="{{ route('inventory.purchase-orders.destroy', $order) }}" method="POST" class="inline" onsubmit="return confirm('سفارش خرید حذف شود؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-red-500">
                                                حذف
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canBulkDeletePurchaseOrders ? 13 : 12 }}" class="px-3 py-6 text-center text-sm text-gray-500">
                                    سفارشی ثبت نشده است.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <div class="shrink-0 border-t border-gray-200 bg-white py-2">
        {{ $purchaseOrders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const perPageSelect = document.querySelector('#per-page-form select[name="per_page"]');
        if (perPageSelect && perPageSelect.form) {
            perPageSelect.addEventListener('change', () => perPageSelect.form.submit());
        }

        const filtersForm = document.getElementById('filters-form');
        if (filtersForm) {
            const filterFields = document.querySelectorAll(
                'select[form="filters-form"], input[form="filters-form"]'
            );
            const dateFields = Array.from(filtersForm.querySelectorAll('input.persian-datepicker[form="filters-form"]'));

            dateFields.forEach((field) => {
                field.addEventListener('change', () => {
                    field.dataset.userSet = '1';
                });
                field.addEventListener('focus', () => {
                    field.dataset.userSet = '1';
                });
            });

            const clearAutoDates = () => {
                dateFields.forEach((field) => {
                    if (field.dataset.userSet !== '1') {
                        field.value = '';
                        const altId = field.getAttribute('data-alt-field') || field.getAttribute('data-target');
                        if (altId) {
                            const alt = document.getElementById(altId);
                            if (alt) alt.value = '';
                        }
                    }
                });
            };

            filterFields.forEach((field) => {
                if (field.tagName === 'SELECT') {
                    field.addEventListener('change', () => {
                        clearAutoDates();
                        filtersForm.submit();
                    });
                    return;
                }

                let timer = null;
                const submitLater = () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        clearAutoDates();
                        filtersForm.submit();
                    }, 400);
                };

                field.addEventListener('input', submitLater);
                field.addEventListener('change', submitLater);
                field.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        clearAutoDates();
                        filtersForm.submit();
                    }
                });
            });
        }

        const form = document.getElementById('bulk-delete-form');
        if (!form) return;

        const rowCheckboxes = Array.from(form.querySelectorAll('.js-po-row-checkbox'));
        const selectAllCheckbox = document.getElementById('purchase-orders-select-all');
        const deleteButton = document.getElementById('bulk-delete-button');

        const updateButtonAndRows = () => {
            const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (deleteButton) {
                deleteButton.disabled = checkedCount === 0;
            }

            rowCheckboxes.forEach((checkbox) => {
                const row = checkbox.closest('.js-po-row');
                if (row) {
                    row.classList.toggle('bg-blue-50', checkbox.checked);
                }
            });

            if (selectAllCheckbox) {
                const enabledCheckboxes = rowCheckboxes.filter((checkbox) => !checkbox.disabled);
                const enabledCount = enabledCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < enabledCount;
                selectAllCheckbox.checked = enabledCount > 0 && checkedCount === enabledCount;
                selectAllCheckbox.disabled = enabledCount === 0;
            }
        };

        rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateButtonAndRows);
        });

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                rowCheckboxes.forEach((checkbox) => {
                    checkbox.checked = Boolean(this.checked);
                });
                updateButtonAndRows();
            });
        }

        form.addEventListener('submit', function (event) {
            const hasSelection = rowCheckboxes.some((checkbox) => checkbox.checked);
            if (!hasSelection) {
                event.preventDefault();
                alert('هیچ سفارشی انتخاب نشده است.');
                return;
            }
        });

        updateButtonAndRows();
    });
</script>
@endpush
