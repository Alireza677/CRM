@php \App\Helpers\DateHelper::class; \App\Helpers\FormOptionsHelper::class; @endphp

<div class="font-vazirmatn" lang="fa" dir="rtl">
    @php
        $lastNote = $lead->lastNote;
        $displayBody = $lastNote?->body ?? '—';

        if ($lastNote) {
            preg_match_all('/@([^\s@]+)/u', $lastNote->body, $matches);
            $mentionedUsernames = array_unique($matches[1] ?? []);
            $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');
            foreach ($mentionedUsers as $username => $user) {
                $displayBody = str_replace("@$username", '@' . $user->name, $displayBody);
            }
        }
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Box 1: اطلاعات عمومی -->
        <div class="lg:col-span-3 rounded-2xl border border-green-200 bg-green-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-green-800 mb-3">اطلاعات عمومی</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">پیشوند</span>
                        <span class="font-medium text-gray-900">{{ $lead->prefix ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">نام و نام خانوادگی</span>
                        <span class="font-medium text-gray-900">{{ $lead->full_name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">نوع مشتری</span>
                        <span class="font-medium text-gray-900">{{ $lead->customer_type ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">صنعت</span>
                        <span class="font-medium text-gray-900">{{ $lead->industry ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">وضعیت</span>
                        <span class="font-medium text-gray-900">{{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($lead->lead_status) ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: اطلاعات تماس -->
        <div class="lg:col-span-3 rounded-2xl border border-sky-200 bg-sky-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-sky-800 mb-3">اطلاعات تماس</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">موبایل</span>
                        <span class="font-medium text-gray-900">{{ $lead->mobile ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تلفن</span>
                        <span class="font-medium text-gray-900">{{ $lead->phone ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ایمیل</span>
                        <span class="font-medium text-gray-900">{{ $lead->email ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">شهر</span>
                        <span class="font-medium text-gray-900">{{ $lead->city ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">استان</span>
                        <span class="font-medium text-gray-900">{{ $lead->state ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: پیگیری‌ها -->
        <div class="lg:col-span-3 rounded-2xl border border-amber-200 bg-amber-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-amber-800 mb-3">پیگیری‌ها</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ ثبت</span>
                        <span class="font-medium text-gray-900">{{ \App\Helpers\DateHelper::toJalali($lead->lead_date) ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ یادداشت</span>
                        <span class="font-medium text-gray-900">
                            {{ optional($lead->lastNote)->created_at ? \App\Helpers\DateHelper::toJalali($lead->lastNote->created_at) : '—' }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-600">آخرین یادداشت</span>
                        <span class="font-medium text-gray-900 whitespace-pre-line">{!! nl2br(e($displayBody)) !!}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ پیگیری بعدی</span>
                        <span class="font-medium text-gray-900">{{ \App\Helpers\DateHelper::toJalali($lead->next_follow_up_date) ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ارجاع به</span>
                        <span class="font-medium text-gray-900">{{ optional($lead->assignedUser)->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 4: منبع سرنخ -->
        <div class="lg:col-span-3 rounded-2xl border border-violet-200 bg-violet-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-violet-800 mb-3">منبع سرنخ</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">منبع</span>
                        <span class="font-medium text-gray-900">{{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ثبت‌کننده یادداشت</span>
                        <span class="font-medium text-gray-900">{{ optional(optional($lead->lastNote)->user)->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 5: اطلاعات ساختمان -->
        <div class="lg:col-span-12 rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition">
            <div class="p-5 space-y-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-semibold text-gray-900">اطلاعات ساختمان</h2>
                    <p class="text-sm text-gray-500">مرور داده‌های فنی برای طراحی یا پیشنهاد سیستم</p>
                </div>

                @php
                    $formatMetric = function ($value) {
                        if ($value === null || $value === '') {
                            return '—';
                        }
                        if (is_numeric($value)) {
                            $normalized = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
                            return $normalized === '' ? '0' : $normalized;
                        }
                        return $value;
                    };
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">کاربری ساختمان</span>
                        <span class="font-semibold text-gray-900">{{ $lead->building_usage ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">دمای موردنیاز داخل (°C)</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->internal_temperature) }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">دمای خارج ساختمان (°C)</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->external_temperature) }}</span>
                    </div>

                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">طول ساختمان (متر)</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->building_length) }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">عرض ساختمان (متر)</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->building_width) }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">ارتفاع کناره (متر)</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->eave_height) }}</span>
                    </div>

                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">ارتفاع تاج سقف (متر)</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->ridge_height) }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">جنس دیوار</span>
                        <span class="font-semibold text-gray-900">{{ $lead->wall_material ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">وضعیت عایق</span>
                        <span class="font-semibold text-gray-900">
                            @php
                                $insulationMap = ['good' => 'خوب', 'medium' => 'متوسط', 'weak' => 'ضعیف'];
                            @endphp
                            {{ $lead->insulation_status ? ($insulationMap[$lead->insulation_status] ?? $lead->insulation_status) : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">تعداد سامانه موضعی 45kw محاسبه شده</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->spot_heating_systems) }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">تعداد سامانه مرکزی آگرین محاسبه شده</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->central_200_systems) }}</span>
                    </div>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <span class="text-gray-500">تعداد سامانه مرکزی اوها ۳۰۰ محاسبه شده</span>
                        <span class="font-semibold text-gray-900">{{ $formatMetric($lead->central_300_systems) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
