<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">پیش‌فاکتورها</h3>
        <a href="{{ route('sales.proformas.create', [
        'opportunity_id' => $opportunity->id,
        'contact_id' => $opportunity->contact_id,
        'organization_id' => $opportunity->organization_id
    ]) }}"
   class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
    ایجاد پیش‌فاکتور
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
                    
                    <th class="px-4 py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opportunity->proformas as $proforma)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $proforma->proforma_number ?? '---' }}</td>

                        <td class="px-4 py-2">
                            {{ $proforma->proforma_date ? jdate($proforma->proforma_date)->format('Y/m/d') : '---' }}
                        </td>

                        @php
                            $stages = \App\Helpers\FormOptionsHelper::proformaStages();
                        @endphp

                        <td class="px-4 py-2">
                            {{ $stages[$proforma->proforma_stage] ?? $proforma->proforma_stage ?? '---' }}
                        </td>

                        <td class="px-4 py-2">
                            {{-- اگر می‌خواهید دو رقم اعشار نشان بدهد: number_format(..., 2) --}}
                            {{ isset($proforma->total_amount) ? number_format((float)$proforma->total_amount, 0) . ' تومان' : '---' }}
                        </td>

                        <td class="px-4 py-2 space-x-3 space-x-reverse">
                            <a href="{{ route('sales.proformas.show', $proforma) }}"
                               class="text-blue-600 hover:underline text-xs">مشاهده</a>
                            <a href="{{ route('sales.proformas.edit', $proforma) }}"
                               class="text-amber-600 hover:underline text-xs">ویرایش</a>
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
