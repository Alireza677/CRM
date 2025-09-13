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

                    // نرمالایز برای مقایسه‌ی دقیق (Null/خالی، Trim، تاریخ)
                    $normalize = function ($v, $k) {
                        if (is_string($v)) { $v = trim($v); if ($v === '') $v = null; }
                        if ($k === 'next_follow_up_date' && !empty($v)) {
                            try { return \Carbon\Carbon::parse($v)->startOfDay()->toDateString(); } catch (\Exception $e) {}
                        }
                        return $v;
                    };

                    // تبدیل برای نمایش زیباتر (نام کاربر/تاریخ/…)
                    $display = function ($v, $k) use ($users) {
                        if ($k === 'assigned_to' && is_numeric($v)) {
                            return $users[$v] ?? $v;
                        }
                        if ($k === 'next_follow_up_date' && !empty($v)) {
                            try { return jdate($v)->format('Y/m/d'); } catch (\Exception $e) { /* ignore */ }
                        }
                        return \App\Helpers\UpdateHelper::beautify($v ?? '-', $k);
                    };
                @endphp

                @if (!empty($new))
                    <ul class="mt-2 text-sm space-y-1 text-gray-700">
                        @foreach($new as $key => $newRaw)
                            @continue(!isset($fields[$key])) {{-- فقط فیلدهای تعریف‌شده --}}
                            @php
                                $oldRaw = $old[$key] ?? null;

                                // مقایسه‌ی نرمالایز شده؛ اگر برابر باشند، نمایش نده
                                $oldNorm = $normalize($oldRaw, $key);
                                $newNorm = $normalize($newRaw, $key);
                            @endphp
                            @continue($oldNorm === $newNorm)

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
