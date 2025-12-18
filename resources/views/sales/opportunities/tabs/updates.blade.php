@php
    use App\Helpers\DateHelper;
    use App\Helpers\UpdateHelper;
    use App\Helpers\FormOptionsHelper;
@endphp

@php
    $leadCreationOrderedKeys = [
        'full_name','company','lead_source','lead_status','customer_type','assigned_to','next_follow_up_date','mobile','phone','email'
    ];
    $users = \App\Models\User::pluck('name', 'id')->toArray();
@endphp

<div class="space-y-6">
    @forelse($activities as $activity)
        <div class="bg-white shadow-sm rounded-lg p-4 border relative">
            {{-- تاریخ شمسی با ساعت کامل --}}
            <div class="absolute top-4 left-4 text-xs text-gray-400">
                {{ DateHelper::toJalali($activity->created_at, 'H:i Y/m/d') }}
            </div>

            @php
                $eventType = $activity->event ?? $activity->description ?? null;
                $isCreated = $eventType === 'created';
                $isProformaCreated = $eventType === 'proforma_created';
                $isDocumentVoid = in_array($eventType, ['document_voided', 'document_unvoided'], true);
                $copiedFromLead = ($activity->properties['copied_from'] ?? null) === 'lead';
            @endphp

            @if($isCreated)
                <div class="text-sm mb-2 font-semibold text-green-700">فرصت فروش جدید ایجاد شد</div>
                @if($copiedFromLead)
                    <div class="text-xs text-gray-500 mb-2">این رویداد از لاگ‌های سرنخ منتقل شده است.</div>
                @endif

                @php
                    $attrs = $activity->properties['attributes'] ?? [];
                    $orderedKeys = $copiedFromLead
                        ? $leadCreationOrderedKeys
                        : ['name', 'type', 'stage', 'organization_id', 'contact_id', 'source', 'assigned_to', 'success_rate', 'amount', 'next_follow_up', 'description'];
                @endphp

                <ul class="text-sm text-gray-800 space-y-1">
                    @foreach($orderedKeys as $key)
                        @php
                            $raw = $attrs[$key] ?? null;
                            $value = null;

                            if ($raw === null || $raw === '') {
                                $value = null;
                            } else {
                                switch ($key) {
                                    case 'assigned_to':
                                        $value = $users[$raw] ?? $raw;
                                        break;
                                    case 'organization_id':
                                        $value = \App\Models\Organization::find($raw)?->name ?? $raw;
                                        break;
                                    case 'contact_id':
                                        $contact = \App\Models\Contact::find($raw);
                                        $value = $contact->full_name
                                            ?? trim((($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')))
                                            ?: $raw;
                                        break;
                                    case 'stage':
                                        $value = FormOptionsHelper::getOpportunityStageLabel($raw);
                                        break;
                                    case 'source':
                                        $value = FormOptionsHelper::getOpportunitySourceLabel($raw);
                                        break;
                                    case 'next_follow_up':
                                        $value = UpdateHelper::beautify($raw, 'next_follow_up');
                                        break;
                                    default:
                                        $value = UpdateHelper::beautify($raw, $key);
                                        break;
                                }
                            }
                        @endphp

                        @if(!is_null($value) && $value !== '')
                            <li>
                                <strong>{{ __("fields.$key") }}</strong>
                                <span class="ml-2 text-gray-700">{{ $value }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @elseif($isProformaCreated)
                @php
                    $proformaId = $activity->properties['proforma_id'] ?? null;
                    $proformaNumber = $activity->properties['proforma_number'] ?? null;
                    $proformaLabel = $proformaNumber
                        ? "پیش‌فاکتور شماره {$proformaNumber}"
                        : 'پیش‌فاکتور';
                @endphp

                <div class="text-sm mb-1 font-semibold text-blue-700">
                    @if($proformaId)
                        <a href="{{ route('sales.proformas.show', $proformaId) }}" class="hover:underline">
                            {{ $proformaLabel }}
                        </a>
                    @else
                        {{ $proformaLabel }}
                    @endif
                    <span class="text-gray-700 font-normal">برای این فرصت ثبت شد.</span>
                </div>
                @if($proformaNumber)
                    <div class="text-xs text-gray-500">شماره مرجع: {{ $proformaNumber }}</div>
                @endif
            @elseif($isDocumentVoid)
                @php
                    $documentTitle = $activity->properties['document_title'] ?? null;
                    $documentId = $activity->properties['document_id'] ?? null;
                    $docLabel = $documentTitle ?: ($documentId ? ('سند #' . $documentId) : 'سند');
                    $actorName = $activity->causer->name ?? 'سیستم';
                    $actionText = $eventType === 'document_voided' ? 'باطل شد' : 'از ابطال خارج شد';
                    $at = DateHelper::toJalali($activity->created_at, 'H:i Y/m/d');
                @endphp
                <div class="text-sm mb-1 font-semibold text-amber-700">
                    {{ $activity->description ?? ($docLabel . ' توسط ' . $actorName . ' در ' . $at . ' ' . $actionText . '.') }}
                </div>
            @else
                <div class="text-sm mb-2">
                    <span class="font-semibold text-blue-700">{{ $activity->causer->name ?? 'سیستم' }}</span>
                    تغییری ایجاد کرد.
                </div>
                @if($copiedFromLead)
                    <div class="text-xs text-gray-500 mb-2">این رویداد از لاگ‌های سرنخ منتقل شده است.</div>
                @endif

                <ul class="text-sm text-gray-800 space-y-1">
                    @foreach($activity->properties['attributes'] ?? [] as $key => $new)
                        @php
                            $old = $activity->properties['old'][$key] ?? null;

                            if (in_array($key, ['assigned_to', 'converted_by'], true)) {
                                $oldValue = $users[$old] ?? $old;
                                $newValue = $users[$new] ?? $new;
                            } else {
                                $oldValue = UpdateHelper::beautify($old, $key);
                                $newValue = UpdateHelper::beautify($new, $key);
                            }
                        @endphp

                        @if($oldValue !== $newValue)
                            <li>
                                <strong>{{ __("fields.$key") }}</strong>
                                تغییر یافت از
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded">{{ $oldValue }}</span>
                                به
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded">{{ $newValue }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <div class="text-center text-gray-400">هیچ بروزرسانی‌ای ثبت نشده است.</div>
    @endforelse
</div>

