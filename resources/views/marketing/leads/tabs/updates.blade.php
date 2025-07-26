@php
    use App\Helpers\UpdateHelper;

    $fields = [
        'title' => 'عنوان',
        'stage' => 'مرحله',
        'lead_status' => 'وضعیت',
        'customer_type' => 'نوع مشتری',
        'lead_source' => 'منبع',
        'assigned_to' => 'ارجاع شده به',
        'success_rate' => 'نرخ موفقیت',
        'amount' => 'مبلغ',
        'next_follow_up_date' => 'پیگیری بعدی',
    ];

    $users = \App\Models\User::pluck('name', 'id')->toArray();
@endphp

<div class="space-y-4" dir="rtl">
    @forelse($lead->activities()->latest()->get() as $activity)
        <div class="flex justify-end">
            <div class="bg-white shadow rounded-md p-4 w-full sm:w-3/4 text-right space-y-2">
                <div class="text-sm text-gray-600 mb-1 text-right">
                    {{ jdate($activity->created_at)->format('H:i Y/m/d') }}
                    {{ $activity->causer ? 'توسط ' . $activity->causer->name : 'سیستم' }}
                </div>

                <div class="text-sm text-gray-800">
                    {!! $activity->description !!}
                </div>

                @php
                    $attributes = $activity->getExtraProperty('attributes');
                    $old = $activity->getExtraProperty('old') ?? [];
                    $new = $attributes ?? [];
                @endphp

                @if (!empty($new))
                    <ul class="mt-2 text-sm space-y-1 text-gray-700">
                        @foreach($new as $key => $value)
                            @if(isset($fields[$key]))
                                <li class="flex flex-row-reverse justify-end items-center gap-1 flex-wrap">
                                <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">
                                    {{ $key === 'next_follow_up_date' && !empty($value)
                                        ? jdate($value)->format('Y/m/d ')
                                        : (is_numeric($value) && $key === 'assigned_to'
                                            ? ($users[$value] ?? $value)
                                            : \App\Helpers\UpdateHelper::beautify($value, $key)) }}
                                </span>

                                <span>به</span>

                                <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                                    {{ $key === 'next_follow_up_date' && isset($old[$key]) && !empty($old[$key])
                                        ? jdate($old[$key])->format('Y/m/d ')
                                        : (is_numeric($old[$key] ?? '') && $key === 'assigned_to'
                                            ? ($users[$old[$key]] ?? $old[$key])
                                            : \App\Helpers\UpdateHelper::beautify($old[$key] ?? '-', $key)) }}
                                </span>


                                    <span>از</span>
                                    <span class="text-gray-600">{{ $fields[$key] }}</span>
                                    <span>تغییر یافت</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 text-right" dir="rtl">هیچ بروزرسانی ثبت نشده است.</p>
    @endforelse
</div>
