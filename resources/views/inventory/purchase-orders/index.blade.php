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

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
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
              </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                @php use Morilog\Jalali\Jalalian; @endphp
                @forelse($purchaseOrders as $order)
                  <tr>
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
                  </tr>
                @empty
                  <tr>
                    <td colspan="11" class="text-center py-4 text-sm text-gray-500">سفارشی ثبت نشده است.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            {{ $purchaseOrders->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
