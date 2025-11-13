<div class="bg-white p-6 rounded shadow" dir="rtl">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">آیتم‌های سفارش خرید</h3>

    @php
        $items = $purchaseOrder->items ?? collect();
        $total = $items->sum('line_total');
    @endphp

    @if($items->count())
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-right border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">نام آیتم</th>
                        <th class="px-3 py-2 font-medium">تعداد</th>
                        <th class="px-3 py-2 font-medium">واحد</th>
                        <th class="px-3 py-2 font-medium">قیمت واحد</th>
                        <th class="px-3 py-2 font-medium">جمع خط</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($items as $idx => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800">{{ $item->item_name }}</td>
                            <td class="px-3 py-2">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</td>
                            <td class="px-3 py-2">{{ $item->unit }}</td>
                            <td class="px-3 py-2">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-3 py-2 font-semibold">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2" colspan="5">
                            <span class="text-gray-600">جمع اقلام</span>
                        </td>
                        <td class="px-3 py-2 font-bold text-gray-900">{{ number_format((float) $total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 text-sm py-6">هیچ آیتمی ثبت نشده است.</div>
    @endif
</div>

