<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6" dir="rtl">
    
    {{-- باکس خلاصه فرصت --}}
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">خلاصه فرصت</h2>

        <div class="space-y-3">
            {{-- عنوان فرصت --}}
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">عنوان فرصت:</span>
                <span class="text-gray-900">{{ $opportunity->name ?? '—' }}</span>
            </div>

            {{-- کاربر مسئول (ارجاع به) --}}
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">کاربر مسئول:</span>
                <span class="text-gray-900">{{ $opportunity->assignedUser->name ?? '—' }}</span>
            </div>

            {{-- مرحله فعلی فرصت (نمایش label به جای key) --}}
            @php
                $stageOptions = \App\Helpers\FormOptionsHelper::opportunityStages();
                $stageKey = $opportunity->getRawOriginal('stage') ?? $opportunity->stage;
                $stageLabel = $stageOptions[$stageKey] ?? ($stageKey ?? '—');
            @endphp
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">مرحله فعلی فرصت:</span>
                <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                    {{ $stageLabel }}
                </span>
            </div>
        </div>
    </div>

    {{-- یادداشت‌های مرتبط --}}
    <div class="bg-blue-50 rounded-lg p-4 shadow-sm border border-blue-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-blue-800">یادداشت‌های مرتبط</h3>
            <p class="text-sm text-blue-700 mt-1">
                تعداد یادداشت‌های ثبت‌شده: {{ $opportunity->notes?->count() ?? 0 }}
            </p>
        </div>
        <i class="fas fa-sticky-note text-3xl text-blue-400"></i>
    </div>

    {{-- وظایف / کارهای مرتبط --}}
    <div class="bg-green-50 rounded-lg p-4 shadow-sm border border-green-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-green-800">کارهای مرتبط</h3>
            <p class="text-sm text-green-700 mt-1">
                تعداد کارهای باز: 0
            </p>
        </div>
        <i class="fas fa-tasks text-3xl text-green-400"></i>
    </div>

    {{-- تماس‌ها --}}
    <div class="bg-purple-50 rounded-lg p-4 shadow-sm border border-purple-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-purple-800">تماس‌ها</h3>
            <p class="text-sm text-purple-700 mt-1">
                به‌زودی لیست تماس‌های مرتبط در این بخش نمایش داده می‌شود.
            </p>
        </div>
        <i class="fas fa-phone-alt text-3xl text-purple-400"></i>
    </div>

    {{-- مخاطب مرتبط --}}
    <div class="bg-yellow-50 rounded-lg p-4 shadow-sm border border-yellow-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-yellow-800">مخاطب مرتبط</h3>
            <p class="text-sm text-yellow-700 mt-1">
                {{ optional($opportunity->contact)->full_name ?? 'مخاطبی برای این فرصت انتخاب نشده است.' }}
            </p>
        </div>
        <i class="fas fa-user-friends text-3xl text-yellow-400"></i>
    </div>

    {{-- پیش‌فاکتورهای مرتبط --}}
    <div class="bg-pink-50 rounded-lg p-4 shadow-sm border border-pink-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-pink-800">پیش‌فاکتورهای مرتبط</h3>
            <p class="text-sm text-pink-700 mt-1">
                تعداد پیش‌فاکتورهای مرتبط: {{ $opportunity->proformas()->count() }}
            </p>
        </div>
        <i class="fas fa-file-invoice text-3xl text-pink-400"></i>
    </div>
</div>

@php
    $commissionRows = $commissionRows ?? [];
@endphp

@role('admin')
<div class="mt-6 bg-white rounded-lg shadow-sm border p-4" dir="rtl">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-gray-800">نقش‌ها و کمیسیون‌ها</h2>
        <span class="text-xs text-gray-500">{{ count($commissionRows) }} مورد</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-2 text-right font-semibold">کاربر</th>
                    <th class="px-4 py-2 text-right font-semibold">نقش</th>
                    <th class="px-4 py-2 text-right font-semibold">درصد</th>
                    <th class="px-4 py-2 text-right font-semibold">مبلغ کمیسیون</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @foreach($commissionRows as $row)
                    @php
                        $percentText = rtrim(rtrim(number_format((float) ($row['percent'] ?? 0), 2, '.', ''), '0'), '.');
                        $amountText = is_null($row['amount'] ?? null)
                            ? '—'
                            : number_format((float) $row['amount'], 0, '.', ',') . ' ریال';
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-right text-gray-800">
                            {{ $row['user_name'] ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-right text-gray-700">
                            {{ $row['role_label'] ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-right text-gray-700">
                            {{ $percentText }} %
                        </td>

                        <td class="px-4 py-2 text-right text-gray-700 font-semibold">
                            {{ $amountText }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endrole
