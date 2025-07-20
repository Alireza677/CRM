<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">پیش‌فاکتورها</h3>
        <a href="{{ route('sales.proformas.create', ['opportunity_id' => $opportunity->id]) }}"
           class="bg-blue-600 text-white px-4 py-2 text-sm rounded hover:bg-blue-700 transition">
            افزودن پیش‌فاکتور
        </a>
    </div>

    @if ($opportunity->proformas && $opportunity->proformas->count())
        <table class="w-full text-sm text-right border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">شماره</th>
                    <th class="px-4 py-2">تاریخ</th>
                    <th class="px-4 py-2">مرحله</th>
                    <th class="px-4 py-2">مبلغ</th>
                    <th class="px-4 py-2">وضعیت</th>
                    <th class="px-4 py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opportunity->proformas as $proforma)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $proforma->number ?? '---' }}</td>
                        <td class="px-4 py-2">{{ jdate($proforma->date)->format('Y/m/d') }}</td>
                        <td class="px-4 py-2">{{ $proforma->stage }}</td>
                        <td class="px-4 py-2">{{ number_format($proforma->total_amount) }} تومان</td>
                        <td class="px-4 py-2">{{ $proforma->status }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:underline text-xs">مشاهده</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-gray-500 mt-4 text-sm">
            هیچ پیش‌فاکتوری ثبت نشده است.
        </div>
    @endif
</div>
