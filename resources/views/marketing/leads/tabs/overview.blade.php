@php
    use App\Models\SalesLead;

    $statusValue = $lead->getStatusValue();
    $isFinalized = in_array($statusValue, [SalesLead::STATUS_CONVERTED, SalesLead::STATUS_DISCARDED], true);
    $finalizedAt = $lead->converted_at ?? ($isFinalized ? $lead->updated_at : null);
    $needsRotationAction = !$isFinalized
        && $lead->assigned_to
        && $lead->pool_status === SalesLead::POOL_ASSIGNED
        && empty($lead->first_activity_at);
    $rotationDueAt = $lead->rotation_due_at;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">

    <!-- اطلاعات کلی سرنخ -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6 w-full max-w-md">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">اطلاعات کلی سرنخ</h2>

        <div class="space-y-3">

            <!-- شرکت -->
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">شرکت:</span>
                <span class="text-gray-900">{{ $lead->company ?? '-' }}</span>
            </div>

            <!-- مسئول سرنخ -->
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">مسئول سرنخ:</span>
                <span class="text-gray-900">{{ $lead->assignedUser->name ?? '-' }}</span>
            </div>

            <!-- وضعیت سرنخ -->
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">وضعیت سرنخ:</span>
                <div class="flex items-center gap-2">
                    <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                        {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($lead->lead_status) ?? '-' }}
                    </span>
                    @php
                        $showReengagedBadge = (bool) $lead->is_reengaged;
                        $isWebsiteSource = $lead->lead_source === 'website';
                    @endphp
                    @if($showReengagedBadge)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $isWebsiteSource ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                            پیگیری مجدد انجام شده
                        </span>
                    @endif
                </div>
            </div>

            <!-- تاریخ سرنخ -->
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">تاریخ سرنخ:</span>
                <span class="text-gray-900">
                    {{ \App\Helpers\DateHelper::toJalali($lead->lead_date) ?? '—' }}
                </span>
            </div>

        </div>
    </div>

    {{-- تایمر چرخش / SLA --}}
    <div class="bg-gradient-to-l from-rose-50 via-white to-amber-50 rounded-lg p-4 shadow-sm border border-amber-100 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-700">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-700">
                    <i class="fas fa-clock"></i>
                </span>
                <div class="text-sm font-semibold">موعد تخصیص مجدد</div>
            </div>
            @if($isFinalized)
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">توقف شده</span>
            @elseif($needsRotationAction && $rotationDueAt)
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">فعال</span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">غیرفعال</span>
            @endif
        </div>

        @if($isFinalized)
            <div class="flex items-start gap-3 text-sm text-gray-700">
                <div class="flex-1">
                    <div class="font-medium">پیگیری انجام شد</div>
                    @if($finalizedAt)
                        <div class="text-xs text-gray-500 mt-1">
                            نتیجه نهایی در {{ \App\Helpers\DateHelper::toJalali($finalizedAt, 'H:i Y/m/d') }} ثبت شده است.
                        </div>
                    @else
                        <div class="text-xs text-gray-500 mt-1">
                            نتیجه نهایی ثبت شده است.
                        </div>
                    @endif
                </div>
            </div>
        @elseif($needsRotationAction && $rotationDueAt)
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    زمان باقی‌مانده تا تخصیص مجدد:
                </div>
                <div id="reassign-countdown" class="px-3 py-2 rounded-lg bg-amber-600 text-white text-sm font-mono font-semibold"
                     data-remaining="{{ $rotationRemainingSeconds ?? 0 }}">
                    —
                </div>
            </div>
            <div class="text-xs text-gray-500">
                در صورت عدم اقدام تا موعد فوق، سرنخ طبق قوانین تخصیص منتقل می‌شود.
            </div>
        @else
            <div class="text-sm text-gray-600">
                این سرنخ در حال حاضر مشمول تایمر تخصیص مجدد نیست یا موعد مشخص نشده است.
            </div>
        @endif
    </div>

    {{-- یادداشت‌ها --}}
    <div class="bg-blue-50 rounded-lg p-4 shadow flex items-center justify-between hover:shadow-md transition cursor-pointer"
         data-card-tab="notes" role="button" tabindex="0">
        <div>
            <h3 class="text-lg font-semibold text-blue-800">یادداشت‌ها</h3>
            <p class="text-sm text-blue-600 mt-1">تعداد یادداشت‌ها: {{ $lead->notes_count ?? 0 }}</p>
        </div>
        <i class="fas fa-sticky-note text-3xl text-blue-400"></i>
    </div>

    {{-- بروزرسانی‌ها --}}
    <div class="bg-green-50 rounded-lg p-4 shadow flex items-center justify-between cursor-pointer"
         data-card-tab="updates" role="button" tabindex="0">
        <div>
            <h3 class="text-lg font-semibold text-green-800">بروزرسانی‌ها</h3>
            <p class="text-sm text-green-600 mt-1">تعداد بروزرسانی ها: {{ $lead->activities_count ?? 0 }}</p>
        </div>
        <i class="fas fa-tasks text-3xl text-green-400"></i>
    </div>

    {{-- تماس‌ها --}}
    <div class="bg-purple-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-purple-800">تماس‌ها</h3>
            <p class="text-sm text-purple-600 mt-1">ثبت تماس‌ها و پیگیری‌های انجام‌شده</p>
        </div>
        <i class="fas fa-phone-alt text-3xl text-purple-400"></i>
    </div>

    {{-- مخاطبین --}}
    <div class="bg-yellow-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-yellow-800">مخاطبین</h3>
            <p class="text-sm text-yellow-600 mt-1">تعداد مخاطبین: {{ !empty($lead->contact_id) ? 1 : 0 }}</p>
        </div>
        <i class="fas fa-user-friends text-3xl text-yellow-400"></i>
    </div>

</div>
