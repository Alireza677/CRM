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
    </div>
</div>

