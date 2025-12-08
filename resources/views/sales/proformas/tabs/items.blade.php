<div class="bg-white p-4 md:p-6 rounded-lg">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">
        آیتم‌های ثبت‌شده در پیش‌فاکتور
    </h3>

    @if($items->count())
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-right border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">شرح کالا / خدمت</th>
                        <th class="px-3 py-2 font-medium">تعداد</th>
                        <th class="px-3 py-2 font-medium">واحد</th>
                        <th class="px-3 py-2 font-medium">قیمت واحد (ریال)</th>
                        <th class="px-3 py-2 font-medium">مبلغ کل (ریال)</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($items as $idx => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-500">
                                {{ $idx + 1 }}
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                {{ $item->name }}
                            </td>
                            <td class="px-3 py-2">
                                {{ rtrim(rtrim(number_format((float) ($item->quantity ?? 0), 2, '.', ''), '0'), '.') }}
                            </td>
                            <td class="px-3 py-2">
                                @switch($item->unit_of_use)
                                    @case('device') دستگاه @break
                                    @case('piece')  عدد    @break
                                    @case('meter')  متر    @break
                                    @default {{ $item->unit_of_use }}
                                @endswitch
                            </td>
                            <td class="px-3 py-2">
                                {{ number_format((float) ($item->unit_price ?? 0), 0) }}
                            </td>
                            <td class="px-3 py-2 font-semibold">
                                {{ number_format((float) ($item->total_price ?? 0), 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2 font-medium text-right" colspan="5">
                            جمع جزء (قبل از تخفیف / مالیات)
                        </td>
                        <td class="px-3 py-2 font-bold text-gray-900">
                            {{ number_format($subtotal, 0) }}
                        </td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2 font-medium text-right" colspan="5">
                            مجموع تخفیف (ریال)
                        </td>
                        <td class="px-3 py-2 font-bold text-red-600">
                            {{ number_format($discount, 0) }}
                        </td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2 font-medium text-right" colspan="5">
                            مجموع مالیات (ریال)
                        </td>
                        <td class="px-3 py-2 font-bold text-green-600">
                            {{ number_format($tax, 0) }}
                        </td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2 font-medium text-right" colspan="5">
                            مبلغ نهایی قابل پرداخت
                        </td>
                        <td class="px-3 py-2 font-bold text-gray-900">
                            {{ number_format($grand, 0) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 text-sm py-6">
            هنوز هیچ آیتمی برای این پیش‌فاکتور ثبت نشده است.
        </div>
    @endif
</div>
