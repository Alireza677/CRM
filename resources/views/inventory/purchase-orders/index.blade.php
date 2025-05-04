<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('سفارش‌های خرید') }}
            </h2>
            <a href="{{ route('inventory.purchase-orders.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                ایجاد سفارش خرید
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex gap-4">
                            <div class="flex-1">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="جستجو در موضوع و نام تأمین‌کننده...">
                            </div>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                جستجو
                            </button>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex items-center">
                                            <input type="text" name="subject" value="{{ request('subject') }}"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                   placeholder="موضوع">
                                            <button type="submit" class="sr-only">فیلتر</button>
                                        </form>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex items-center">
                                            <input type="text" name="supplier" value="{{ request('supplier') }}"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                   placeholder="نام تأمین‌کننده">
                                            <button type="submit" class="sr-only">فیلتر</button>
                                        </form>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex items-center">
                                            <input type="date" name="purchase_date" value="{{ request('purchase_date') }}"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                   placeholder="تاریخ خرید">
                                            <button type="submit" class="sr-only">فیلتر</button>
                                        </form>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex items-center">
                                            <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="">وضعیت</option>
                                                <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>ایجاد شد</option>
                                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>تأیید شد</option>
                                                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>تحویل شد</option>
                                            </select>
                                            <button type="submit" class="sr-only">فیلتر</button>
                                        </form>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <form action="{{ route('inventory.purchase-orders.index') }}" method="GET" class="flex items-center">
                                            <input type="text" name="assigned_to" value="{{ request('assigned_to') }}"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                   placeholder="ارجاع به">
                                            <button type="submit" class="sr-only">فیلتر</button>
                                        </form>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($purchaseOrders as $order)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $order->subject }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $order->supplier_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($order->purchase_date)->format('Y/m/d') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
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
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$order->status] }}">
                                                {{ $statusLabels[$order->status] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($order->total_amount) }} ریال
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $order->assigned_to_name }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            هیچ سفارش خریدی یافت نشد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $purchaseOrders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 