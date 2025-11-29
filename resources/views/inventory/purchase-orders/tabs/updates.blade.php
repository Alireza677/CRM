@php
    use App\Helpers\DateHelper;
    use App\Helpers\UpdateHelper;
    use App\Models\PurchaseOrder;

    $fieldLabels = [
        'subject' => 'عنوان سفارش',
        'purchase_type' => 'نوع خرید',
        'supplier_id' => 'تأمین‌کننده',
        'requested_by' => 'درخواست‌کننده',
        'request_date' => 'تاریخ درخواست',
        'purchase_date' => 'تاریخ خرید',
        'needed_by_date' => 'نیاز تا',
        'status' => 'وضعیت',
        'settlement_type' => 'نوع تسویه',
        'usage_type' => 'مورد استفاده',
        'project_name' => 'نام پروژه',
        'operational_expense_type' => 'نوع هزینه جاری',
        'assigned_to' => 'مسئول پیگیری',
        'vat_percent' => 'درصد مالیات',
        'vat_amount' => 'مبلغ مالیات',
        'total_amount' => 'مبلغ سفارش',
        'total_with_vat' => 'جمع با مالیات',
        'previously_paid_amount' => 'مبالغ پرداخت‌شده',
        'remaining_payable_amount' => 'مانده پرداخت',
        'description' => 'توضیحات',
    ];

    $systemFieldLabels = [
        'ready_for_delivery_notified_at' => '    تکمیل تاییدیه ها و آماده خرید',
    ];

    $creationOrder = [
        'subject',
        'purchase_type',
        'supplier_id',
        'requested_by',
        'request_date',
        'purchase_date',
        'needed_by_date',
        'status',
        'settlement_type',
        'usage_type',
        'project_name',
        'operational_expense_type',
        'assigned_to',
        'vat_percent',
        'vat_amount',
        'total_amount',
        'total_with_vat',
        'previously_paid_amount',
        'remaining_payable_amount',
        'description',
    ];

    $userCache = [];
    $supplierCache = [];

    $formatValue = function ($value, string $key) use (&$userCache, &$supplierCache) {
        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        switch ($key) {
            case 'supplier_id':
                $supplierId = (int) $value;
                if ($supplierId <= 0) {
                    return null;
                }
                if (! array_key_exists($supplierId, $supplierCache)) {
                    $supplierCache[$supplierId] = \App\Models\Supplier::find($supplierId)?->name ?? $value;
                }
                return $supplierCache[$supplierId];
            case 'requested_by':
            case 'assigned_to':
                $userId = (int) $value;
                if ($userId <= 0) {
                    return null;
                }
                if (! array_key_exists($userId, $userCache)) {
                    $userCache[$userId] = \App\Models\User::find($userId)?->name ?? $value;
                }
                return $userCache[$userId];
            case 'purchase_type':
                return $value === 'unofficial' ? 'خرید غیررسمی' : 'خرید رسمی';
            case 'status':
                $statuses = PurchaseOrder::statuses();
                return $statuses[$value] ?? $value;
            case 'settlement_type':
                $map = [
                    'cash' => 'نقدی',
                    'credit' => 'اعتباری',
                    'cheque' => 'چک',
                    'operational_expense' => 'هزینه جاری',
                ];
                return $map[$value] ?? $value;
            case 'usage_type':
                $map = [
                    'inventory' => 'تکمیل موجودی انبار',
                    'project' => 'تکمیل پروژه',
                    'both' => 'هر دو',
                    'operational_expense' => 'هزینه جاری',
                ];
                return $map[$value] ?? $value;
            case 'operational_expense_type':
                $map = [
                    'commission' => 'کارمزد/کمیسیون',
                    'installation' => 'نصب',
                    'shipping' => 'حمل و نقل',
                    'workshop_running' => 'اداره کارگاه',
                ];
                return $map[$value] ?? $value;
            case 'request_date':
            case 'purchase_date':
            case 'needed_by_date':
                return DateHelper::toJalali($value, 'Y/m/d');
            case 'vat_percent':
                if (is_numeric($value)) {
                    $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
                    return $formatted === '' ? null : $formatted.'٪';
                }
                break;
            case 'vat_amount':
            case 'total_amount':
            case 'total_with_vat':
            case 'previously_paid_amount':
            case 'remaining_payable_amount':
                if (is_numeric($value)) {
                    return number_format((float) $value);
                }
                break;
        }

        return UpdateHelper::beautify($value, $key);
    };

    $getLabel = function (string $key) use ($fieldLabels) {
        $label = $fieldLabels[$key] ?? __("fields.$key", [], 'fa');
        return $label === "fields.$key" ? $key : $label;
    };
@endphp

<div class="space-y-6">
    @forelse(($activities ?? collect()) as $activity)
        @php
            $eventType = $activity->event ?? $activity->description ?? null;
            $isCreated = $eventType === 'created';
            $isDocument = $eventType === 'document_added';
            $attributes = $activity->properties['attributes'] ?? [];
            $oldValues = $activity->properties['old'] ?? [];
        @endphp
        <div class="bg-white shadow-sm rounded-lg p-4 border relative">
            <div class="absolute top-4 left-4 text-xs text-gray-400">
                {{ DateHelper::toJalali($activity->created_at, 'H:i Y/m/d') }}
            </div>

            @if($isCreated)
                <div class="text-sm mb-2 font-semibold text-green-700">
                    سفارش جدید ایجاد شد
                </div>
                <ul class="text-sm text-gray-800 space-y-1">
                    @foreach($creationOrder as $key)
                        @php
                            $raw = $attributes[$key] ?? null;
                            $value = $formatValue($raw, $key);
                        @endphp
                        @if(! is_null($value))
                            <li class="flex flex-row-reverse justify-between items-center gap-2 flex-wrap">
                                <span class="text-gray-800">{{ $value }}</span>
                                <span class="text-gray-600">{{ $getLabel($key) }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @elseif($isDocument)
                <div class="text-sm mb-3">
                    <span class="font-semibold text-blue-700">{{ $activity->causer->name ?? 'سیستم' }}</span>
                    یک سند جدید بارگذاری کرد.
                </div>
                @php
                    $document = $activity->properties['document'] ?? [];
                    $docTitle = $document['title'] ?? 'بدون عنوان';
                    $docExt = strtoupper($document['extension'] ?? '');
                    $docId = $document['id'] ?? null;
                    $viewUrl = $docId ? route('sales.documents.view', ['document' => $docId]) : null;
                    $downloadUrl = $docId ? route('sales.documents.download', ['document' => $docId]) : null;
                @endphp
                <div class="bg-gray-50 border rounded-md p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">{{ $docTitle }}</div>
                        <div class="text-xs text-gray-500">{{ $docExt ?: 'FILE' }}</div>
                    </div>
                    @if($viewUrl)
                        <div class="flex items-center gap-4 text-sm">
                            <a href="{{ $viewUrl }}" target="_blank" class="text-blue-600 hover:underline">مشاهده</a>
                            @if($downloadUrl)
                                <a href="{{ $downloadUrl }}" class="text-gray-700 hover:underline">دانلود</a>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="text-sm mb-2">
                    <span class="font-semibold text-blue-700">{{ $activity->causer->name ?? 'سیستم' }}</span>
                    تغییری ایجاد کرد.
                </div>
                <ul class="text-sm text-gray-800 space-y-1">
                    @foreach($attributes as $key => $newValueRaw)
                        @php
                            $oldValueRaw = $oldValues[$key] ?? null;
                            $oldValue = $formatValue($oldValueRaw, $key);
                            $newValue = $formatValue($newValueRaw, $key);
                            $label = $systemFieldLabels[$key] ?? $getLabel($key);
                        @endphp
                        @if($oldValue !== $newValue)
                            <li class="flex flex-row-reverse flex-wrap items-center gap-2">
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">{{ $newValue ?? '—' }}</span>
                                <span>به</span>
                                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs">{{ $oldValue ?? '—' }}</span>
                                <span>از</span>
                                <span class="text-gray-600">{{ $label }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <div class="text-center text-gray-400">هیچ به‌روزرسانی‌ای ثبت نشده است.</div>
    @endforelse
</div>
