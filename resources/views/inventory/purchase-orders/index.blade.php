@extends('layouts.app')
@php
    $breadcrumb = [
        ['title' => 'سفارش‌های خرید']
    ];
@endphp

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('سفارش‌های خرید') }}
                </h2>
                <a href="{{ route('inventory.purchase-orders.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    ایجاد سفارش خرید
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex gap-4 mb-4">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="جستجو در موضوع یا تأمین‌کننده...">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">
                            جستجو
                        </button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-xs text-right text-gray-500">موضوع</th>
                                    <th class="px-6 py-3 text-xs text-right text-gray-500">تأمین‌کننده</th>
                                    <th class="px-6 py-3 text-xs text-right text-gray-500">تاریخ خرید</th>
                                    <th class="px-6 py-3 text-xs text-right text-gray-500">وضعیت</th>
                                    <th class="px-6 py-3 text-xs text-right text-gray-500">مبلغ کل</th>
                                    <th class="px-6 py-3 text-xs text-right text-gray-500">ارجاع به</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                    $statusColors = [
                                        'created' => 'bg-blue-100 text-blue-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'delivered' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusLabels = [
                                        'created' => 'ایجاد شد',
                                        'approved' => 'تأیید شد',
                                        'delivered' => 'تحویل شد'
                                    ];
                                @endphp

                                @forelse($purchaseOrders as $order)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $order->subject }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $order->supplier_name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($order->purchase_date)->format('Y/m/d') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-2 inline-flex text-xs font-semibold rounded-full
                                                {{ $statusColors[$order->status] ?? 'bg-red-100 text-red-800' }}">
                                                {{ $statusLabels[$order->status] ?? 'نامشخص' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ number_format($order->total_amount) }} ریال
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $order->assigned_to_name }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-sm text-gray-500">
                                            هیچ سفارشی ثبت نشده است.
                                        </td>
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
