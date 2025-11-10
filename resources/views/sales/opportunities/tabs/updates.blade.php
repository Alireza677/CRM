@php
    use App\Helpers\DateHelper;
    use App\Helpers\UpdateHelper;
    use App\Helpers\FormOptionsHelper;
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
            @endphp

            @if($isCreated)
                <div class="text-sm mb-2 font-semibold text-green-700">فرصت فروش جدید ایجاد شد</div>

                @php
                    $attrs = $activity->properties['attributes'] ?? [];
                    $orderedKeys = [
                        'name', 'type', 'stage', 'organization_id', 'contact_id', 'source', 'assigned_to', 'success_rate', 'amount', 'next_follow_up', 'description'
                    ];
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
                                        $value = \App\Models\User::find($raw)?->name ?? $raw;
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
            @else
                <div class="text-sm mb-2">
                    <span class="font-semibold text-blue-700">{{ $activity->causer->name ?? 'سیستم' }}</span>
                    تغییری ایجاد کرد.
                </div>

                <ul class="text-sm text-gray-800 space-y-1">
                    @foreach($activity->properties['attributes'] ?? [] as $key => $new)
                        @php
                            $old = $activity->properties['old'][$key] ?? null;

                            if ($key === 'assigned_to') {
                                $oldValue = \App\Models\User::find($old)?->name ?? $old;
                                $newValue = \App\Models\User::find($new)?->name ?? $new;
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
