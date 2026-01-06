<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">پیش‌فاکتورها</h3>
        <a href="{{ route('sales.proformas.create', [
        'opportunity_id' => $opportunity->id,
        'contact_id' => $opportunity->contact_id,
        'organization_id' => $opportunity->organization_id,
        'return_to' => route('sales.opportunities.show', $opportunity),
    ]) }}"
   class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
    ایجاد پیش‌فاکتور
</a>

    </div>

    @php
        $proformas = $opportunity->proformas ?? collect();
        $primaryProformaId = $opportunity->primary_proforma_id ?? null;
        if (!$primaryProformaId && $proformas->isNotEmpty()) {
            $primaryProformaId = $proformas->first()->id;
        }
    @endphp

    @if ($proformas->isNotEmpty())
        <div class="overflow-hidden border border-gray-200 rounded"
             data-primary-proforma-url="{{ route('sales.opportunities.primary-proforma', $opportunity) }}">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">شماره</th>
                    <th class="px-4 py-2">تاریخ</th>
                    <th class="px-4 py-2">مرحله</th>
                    
                    <th class="px-4 py-2">مبلغ</th>
                    
                    <th class="px-4 py-2 text-left">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($proformas as $proforma)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">
                            {{ $proforma->proforma_number ?? '---' }}
                            @if($primaryProformaId && (int) $primaryProformaId === (int) $proforma->id)
                                <span class="mr-2 text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded">
                                    پیش‌فاکتور اصلی
                                </span>
                            @endif
                        </td>

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
                            {{ isset($proforma->total_amount) ? number_format((float)$proforma->total_amount, 0) . ' ریال' : '---' }}
                        </td>

                        <td class="px-4 py-2 text-left">
                            <div class="flex items-center gap-2 justify-end">
                                @if(!$primaryProformaId || (int) $primaryProformaId !== (int) $proforma->id)
                                    <button type="button"
                                            class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200"
                                            data-action="set-primary-proforma"
                                            data-proforma-id="{{ $proforma->id }}">
                                        تعیین به‌عنوان اصلی
                                    </button>
                                @endif

                                <a href="{{ route('sales.proformas.show', $proforma) }}"
                                   class="text-blue-600 hover:underline text-xs">مشاهده</a>
                                <a href="{{ route('sales.proformas.edit', $proforma) }}"
                                   class="text-amber-600 hover:underline text-xs">ویرایش</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="text-gray-500 mt-4 text-sm">
            هیچ پیش‌فاکتوری ثبت نشده است.
        </div>
    @endif
</div>
