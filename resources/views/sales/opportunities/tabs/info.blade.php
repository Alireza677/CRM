@php \App\Helpers\DateHelper::class; @endphp

<div class="font-vazirmatn" lang="fa" dir="rtl">
    @php
        $lastNote = $opportunity->lastNote ?? null;
        $displayBody = $lastNote?->body ?? '—';

        if ($lastNote) {
            preg_match_all('/@([^\s@]+)/u', $lastNote->body, $matches);
            $mentionedUsernames = array_unique($matches[1] ?? []);
            if (!empty($mentionedUsernames)) {
                $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');
                foreach ($mentionedUsers as $username => $user) {
                    $displayBody = str_replace("@{$username}", '@' . $user->name, $displayBody);
                }
            }
        }
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Box 1: اطلاعات اصلی -->
        <div class="lg:col-span-3 rounded-2xl border border-green-200 bg-green-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-green-800 mb-3">اطلاعات اصلی</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">نام فرصت فروش</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">سازمان</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->organization->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">مخاطب</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->contact->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">کاربری ساختمان</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->building_usage ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: وضعیت فروش -->
        <div class="lg:col-span-3 rounded-2xl border border-sky-200 bg-sky-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-sky-800 mb-3">وضعیت فروش</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">مرحله فروش</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->stage ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">درصد موفقیت</span>
                        <span class="font-medium text-gray-900">{{ isset($opportunity->success_rate) ? ($opportunity->success_rate . '%') : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">نوع</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->type ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">منبع</span>
                        <span class="font-medium text-gray-900">{{ $opportunity->source ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: پیگیری و ارجاع -->
        <div class="lg:col-span-3 rounded-2xl border border-amber-200 bg-amber-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-amber-800 mb-3">پیگیری و ارجاع</h2>
                @php
                    $roleLabels = [
                        'acquirer' => 'جذب کننده',
                        'relationship_owner' => 'مالک اصلی',
                        'closer' => 'نهایی کننده',
                        'execution_owner' => 'پشتیبان فنی',
                    ];
                    $roleAssignmentsByType = $opportunity->roleAssignments?->keyBy('role_type') ?? collect();
                @endphp
                <div class="space-y-2 text-sm">
                    @foreach($roleLabels as $roleType => $label)
                        @php
                            $assignment = $roleAssignmentsByType->get($roleType);
                            $roleUser = $assignment?->user;
                        @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-medium text-gray-900">{{ $roleUser?->name ?: ($roleUser?->username ?: '—') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Box 4: یادداشت‌ها و توضیحات -->
        <div class="lg:col-span-3 rounded-2xl border border-violet-200 bg-violet-50/80 shadow-sm hover:shadow-md transition">
            <div class="p-5">
                <h2 class="text-base font-semibold text-violet-800 mb-3">یادداشت‌ها و توضیحات</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-600">آخرین یادداشت</span>
                        <span class="font-medium text-gray-900 whitespace-pre-line">{!! nl2br(e($displayBody)) !!}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاریخ یادداشت</span>
                        <span class="font-medium text-gray-900">{{ $lastNote?->created_at ? \App\Helpers\DateHelper::toJalali($lastNote->created_at) : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ثبت‌کننده یادداشت</span>
                        <span class="font-medium text-gray-900">{{ $lastNote?->user?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-600">توضیحات</span>
                        <span class="font-medium text-gray-900 whitespace-pre-line">{{ $opportunity->description ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
