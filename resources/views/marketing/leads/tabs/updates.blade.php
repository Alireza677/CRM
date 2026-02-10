@php
    $lead = $lead ?? $model ?? null;
    use App\Helpers\UpdateHelper;

    $fields = [
        'title' => 'عنوان',
        'stage' => 'مرحله',
        'lead_status' => 'وضعیت',
        'customer_type' => 'نوع مشتری',
        'lead_source' => 'منبع',
        'assigned_to' => 'ارجاع شده به',
        'contact_id' => 'مخاطب',
        'success_rate' => 'نرخ موفقیت',
        'amount' => 'مبلغ',
        'next_follow_up_date' => 'پیگیری بعدی',
    ];

    $creationLabels = [
        'full_name' => 'نام کامل',
        'company' => 'شرکت',
        'lead_source' => 'منبع',
        'lead_status' => 'وضعیت',
        'customer_type' => 'نوع مشتری',
        'assigned_to' => 'ارجاع شده به',
        'contact_id' => 'مخاطب',
        'next_follow_up_date' => 'پیگیری بعدی',
        'mobile' => 'موبایل',
        'phone' => 'تلفن',
        'email' => 'ایمیل',
    ];

    $users = \App\Models\User::pluck('name', 'id')->toArray();
    $contacts = \App\Models\Contact::select('id', 'first_name', 'last_name')
        ->get()
        ->mapWithKeys(function ($contact) {
            $name = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
            return [$contact->id => ($name !== '' ? $name : ('مخاطب #' . $contact->id))];
        })
        ->toArray();
    $resolveContactName = function ($value) use (&$contacts) {
        if (!is_numeric($value)) return $value;
        $id = (int) $value;
        if (!isset($contacts[$id])) {
            $contact = \App\Models\Contact::select('id', 'first_name', 'last_name')->find($id);
            if ($contact) {
                $name = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
                $contacts[$id] = $name !== '' ? $name : ('مخاطب #' . $id);
            }
        }
        return $contacts[$id] ?? $value;
    };
@endphp

@if(!$lead)
    <div class="text-sm text-gray-500">بروزرسانی‌ها در دسترس نیست.</div>
@else

<div class="space-y-4" dir="rtl">
    @forelse($lead->activities()->latest()->get() as $activity)
        <div class="flex justify-start">
            <div class="bg-white shadow rounded-md p-4 w-full sm:w-3/4 text-right space-y-2">
                <div class="text-sm text-gray-600 mb-1 text-right">
                    {{ jdate($activity->created_at)->format('H:i Y/m/d') }}
                    {{ $activity->causer ? 'توسط ' . $activity->causer->name : 'سیستم' }}
                </div>

                @php
                    $eventType = $activity->event ?? $activity->description ?? null;
                    $isCreated = $eventType === 'created';
                    $isContactAttached = $eventType === 'contact_attached';
                    $isContactDetached = $eventType === 'contact_detached';
                @endphp

                @if($isContactAttached)
                    @php
                        $contactName = $activity->getExtraProperty('contact_name');
                        if (empty($contactName)) {
                            $contactName = $resolveContactName($activity->getExtraProperty('contact_id'));
                        }
                    @endphp
                    <div class="text-sm text-green-700 font-semibold">
                        مخاطب {{ $contactName }} به این سرنخ اضافه شد
                    </div>
                @elseif($isContactDetached)
                    @php
                        $contactName = $activity->getExtraProperty('contact_name');
                        if (empty($contactName)) {
                            $contactName = $resolveContactName($activity->getExtraProperty('contact_id'));
                        }
                    @endphp
                    <div class="text-sm text-red-700 font-semibold">
                        ارتباط مخاطب {{ $contactName }} حذف شد
                    </div>
                @elseif($isCreated)
                    <div class="text-sm text-green-700 font-semibold">سرنخ جدید ایجاد شد</div>
                @else
                    <div class="text-sm text-gray-800"> تغییری ایجاد شد</div>
                @endif

                @php
                    $attributes = $activity->getExtraProperty('attributes');
                    $old = $activity->getExtraProperty('old') ?? [];
                    $new = $attributes ?? [];

                    $normalize = function ($v, $k) {
                        if (is_string($v)) { $v = trim($v); if ($v === '') $v = null; }
                        if ($k === 'next_follow_up_date' && !empty($v)) {
                            try { return \Carbon\Carbon::parse($v)->startOfDay()->toDateString(); } catch (\Exception $e) {}
                        }
                        return $v;
                    };

                    $display = function ($v, $k) use ($users, $resolveContactName) {
                        if ($k === 'assigned_to' && (is_numeric($v) || is_string($v))) {
                            return $users[$v] ?? $v;
                        }
                        if ($k === 'contact_id' && (is_numeric($v) || is_string($v))) {
                            return $resolveContactName($v);
                        }
                        if ($k === 'next_follow_up_date' && !empty($v)) {
                            try { return jdate($v)->format('Y/m/d'); } catch (\Exception $e) {}
                        }
                        return UpdateHelper::beautify($v ?? '-', $k);
                    };

                    $conversionInfo = null;
                    if (! $isCreated) {
                        $newConvertedId = $new['converted_opportunity_id'] ?? null;
                        $oldConvertedId = $old['converted_opportunity_id'] ?? null;
                        if (!empty($newConvertedId) && empty($oldConvertedId)) {
                            $conversionInfo = [
                                'opportunity_id' => $newConvertedId,
                                'converted_by'   => $new['converted_by'] ?? null,
                                'converted_at'   => $new['converted_at'] ?? null,
                            ];
                        }
                    }
                @endphp

                @if($isContactAttached || $isContactDetached)
                    @php $attributes = []; $old = []; $new = []; @endphp
                @elseif($isCreated)
                    @php
                        $orderedKeys = [
                            'full_name','company','lead_source','lead_status','customer_type','assigned_to','next_follow_up_date','mobile','phone','email'
                        ];
                    @endphp
                    <ul class="mt-2 text-sm space-y-1 text-gray-700">
                        @foreach($orderedKeys as $key)
                            @php $val = $new[$key] ?? null; @endphp
                            @continue(is_null($val) || (is_string($val) && trim($val) === ''))
                            <li class="flex flex-row-reverse justify-end items-center gap-1 flex-wrap">
                                <span class="text-gray-800">{{ $display($val, $key) }}</span>
                                <span class="text-gray-600">{{ $creationLabels[$key] ?? $key }}</span>
                            </li>
                        @endforeach
                    </ul>
                @elseif (!empty($new))
                    @if(!empty($conversionInfo))
                        @php
                            $conversionUserName = null;
                            if (!empty($conversionInfo['converted_by'])) {
                                $conversionUserName = $users[$conversionInfo['converted_by']] ?? null;
                            }

                            $conversionDateText = null;
                            if (!empty($conversionInfo['converted_at'])) {
                                try {
                                    $conversionDateText = jdate(\Carbon\Carbon::parse($conversionInfo['converted_at']))->format('Y/m/d H:i');
                                } catch (\Exception $e) {
                                    $conversionDateText = null;
                                }
                            }
                            if (empty($conversionDateText)) {
                                $conversionDateText = jdate($activity->created_at)->format('Y/m/d H:i');
                            }

                            $opportunityLink = null;
                            if (!empty($conversionInfo['opportunity_id'])) {
                                $opportunityLink = route('sales.opportunities.show', $conversionInfo['opportunity_id']);
                            }
                        @endphp
                        <div class="mt-2 text-sm text-gray-700 space-y-1">
                            <p>
                                سرنخ فروش
                                @if($conversionUserName)
                                    توسط <span class="font-semibold">{{ $conversionUserName }}</span>
                                @endif
                                در تاریخ <span class="font-semibold">{{ $conversionDateText }}</span>
                                به فرصت فروش تبدیل شد.
                            </p>
                            @if($opportunityLink)
                                <a href="{{ $opportunityLink }}" class="text-blue-600 hover:text-blue-800 underline">
                                    نمایش فرصت
                                </a>
                            @endif
                        </div>
                    @endif

                    <ul class="mt-2 text-sm space-y-1 text-gray-700">
                        @foreach($new as $key => $newRaw)
                            @continue(!isset($fields[$key]))
                            @php
                                $oldRaw = $old[$key] ?? null;
                                $oldNorm = $normalize($oldRaw, $key);
                                $newNorm = $normalize($newRaw, $key);
                            @endphp
                            @continue($oldNorm === $newNorm)

                            @if($key === 'contact_id')
                                @php
                                    $newName = $display($newRaw, $key);
                                    $oldName = $oldRaw ? $display($oldRaw, $key) : null;
                                @endphp
                                <li class="flex flex-row-reverse justify-end items-center gap-1 flex-wrap">
                                    @if(!empty($oldName))
                                        
                                        <span class="text-gray-800">{{ $oldName }}</span>
                                        <span>به</span>
                                        <span class="text-gray-800">{{ $newName }}</span>
                                        <span>تغییر یافت: </span>
                                        <span class="text-gray-800">مخاطب</span>
                                    @else
                                        <span class="text-gray-800">مخاطب</span>
                                        <span class="text-gray-800">{{ $newName }}</span>
                                        <span>به این سرنخ اضافه شد</span>
                                    @endif
                                </li>
                                @continue
                            @endif

                            <li class="flex flex-row-reverse justify-end items-center gap-1 flex-wrap">
                                <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">
                                    {{ $display($newRaw, $key) }}
                                </span>
                                <span>به</span>
                                <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                                    {{ $display($oldRaw, $key) }}
                                </span>
                                <span>از</span>
                                <span class="text-gray-600">{{ $fields[$key] }}</span>
                                <span>تغییر یافت</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 text-right" dir="rtl">هیچ بروزرسانی ثبت نشده است.</p>
    @endforelse
</div>
@endif
