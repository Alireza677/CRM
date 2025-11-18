@extends('layouts.app')

@section('content')
  <div class="py-12">
    <div class=" mx-auto sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">سفارش‌های خرید</h2>
        <a href="{{ route('inventory.purchase-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">سفارش خرید جدید</a>
      </div>

      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex gap-4 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="جستجوی عنوان یا تأمین‌کننده...">
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">جستجو</button>
          </form>

          @php
            use Morilog\Jalali\Jalalian;
            $canBulkDeletePurchaseOrders = auth()->check() && auth()->user()->can('purchase_orders.delete.own');
          @endphp

          <form id="bulk-delete-form" action="{{ route('inventory.purchase-orders.bulk-destroy') }}" method="POST" class="space-y-4">
            @csrf
            @method('DELETE')

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  @if ($canBulkDeletePurchaseOrders)
                    <th class="px-4 py-3 text-xs text-center text-gray-500">
                      <input type="checkbox" id="purchase-orders-select-all" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                    </th>
                  @endif
                  <th class="px-4 py-3 text-xs text-right text-gray-500">شماره</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">عنوان</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">نوع</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">تأمین‌کننده</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">تاریخ درخواست</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">نیاز تا</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">وضعیت</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">جمع کل</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">پرداخت‌شده</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">مانده</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">درخواست‌کننده</th>
                  <th class="px-4 py-3 text-xs text-right text-gray-500">اقدامات</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                @forelse($purchaseOrders as $order)
                  <tr class="js-po-row" data-order-id="{{ $order->id }}">
                    @if ($canBulkDeletePurchaseOrders)
                      <td class="px-4 py-3 text-sm text-center">
                        @can('delete', $order)
                          <input type="checkbox" name="selected_orders[]" value="{{ $order->id }}" class="h-4 w-4 text-blue-600 border-gray-300 rounded js-po-row-checkbox">
                        @else
                          <span class="text-gray-300 text-xs">—</span>
                        @endcan
                      </td>
                    @endif
                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                      {{ $order->po_number ?? ('#'.$order->id) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-blue-700">
                      <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="hover:underline">
                        {{ $order->subject }}
                      </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $order->purchase_type === 'unofficial' ? 'غیررسمی' : 'رسمی' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $order->supplier_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">
                      {{ $order->request_date ? Jalalian::fromCarbon($order->request_date)->format('Y/m/d') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">
                      {{ $order->needed_by_date ? Jalalian::fromCarbon($order->needed_by_date)->format('Y/m/d') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                      @php
                        $statusLabels = \App\Models\PurchaseOrder::statuses();
                        $statusBadgeMap = [
                          'created' => 'bg-blue-100 text-blue-800',
                          'supervisor_approval' => 'bg-amber-100 text-amber-800',
                          'manager_approval' => 'bg-yellow-100 text-yellow-800',
                          'accounting_approval' => 'bg-teal-100 text-teal-800',
                          'purchasing' => 'bg-indigo-100 text-indigo-800',
                          'purchased' => 'bg-green-100 text-green-800',
                          'warehouse_delivered' => 'bg-green-100 text-green-800',
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
                    <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($order->total_amount, 0) }} ریال</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($order->previously_paid_amount ?? 0, 0) }} ریال</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($order->remaining_payable_amount ?? 0, 0) }} ریال</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $order->requested_by_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">
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
                    <td colspan="{{ $canBulkDeletePurchaseOrders ? 13 : 12 }}" class="text-center py-4 text-sm text-gray-500">سفارشی ثبت نشده است.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

            @if ($canBulkDeletePurchaseOrders)
              <div class="flex justify-end">
                <button type="submit" id="bulk-delete-button" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                  حذف انتخاب‌شده‌ها
                </button>
              </div>
            @endif
          </form>

          <div class="mt-4">
            {{ $purchaseOrders->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('bulk-delete-form');
      if (!form) {
        return;
      }

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

        if (!window.confirm('سفارش‌های انتخاب‌شده حذف شوند؟')) {
          event.preventDefault();
        }
      });

      updateButtonAndRows();
    });
  </script>
@endpush
