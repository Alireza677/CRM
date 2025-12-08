<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
    {{-- باکس خلاصه فرصت --}}
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">خلاصه فرصت</h2>

        <div class="space-y-3">
            {{-- عنوان فرصت --}}
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">عنوان فرصت :</span>
                <span class="text-gray-900">{{ $opportunity->name ?? '-' }}</span>
            </div>

            {{-- کاربر مسئول --}}
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">کاربر مسئول :</span>
                <span class="text-gray-900">{{ $opportunity->assignedTo->name ?? '-' }}</span>
            </div>

            {{-- مرحله فعلی فرصت --}}
            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                <span class="text-gray-600">مرحله فعلی فرصت :</span>
                <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                    {{ $opportunity->stage ?? '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- یادداشت‌های مرتبط --}}
    <div class="bg-blue-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-blue-800">یادداشت‌های مرتبط</h3>
            <p class="text-sm text-blue-600 mt-1">
                تعداد یادداشت‌های ثبت‌شده: {{ $opportunity->notes->count() ?? 0 }}
            </p>
        </div>
        <i class="fas fa-sticky-note text-3xl text-blue-400"></i>
    </div>

    {{-- وظایف / کارهای مرتبط --}}
    <div class="bg-green-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-green-800">کارهای مرتبط</h3>
            <p class="text-sm text-green-600 mt-1">
                تعداد کارهای باز: 0
            </p>
        </div>
        <i class="fas fa-tasks text-3xl text-green-400"></i>
    </div>

    {{-- تماس‌ها --}}
    <div class="bg-purple-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-purple-800">تماس‌ها</h3>
            <p class="text-sm text-purple-600 mt-1">
                به‌زودی لیست تماس‌های مرتبط در این بخش نمایش داده می‌شود.
            </p>
        </div>
        <i class="fas fa-phone-alt text-3xl text-purple-400"></i>
    </div>

    {{-- مخاطب مرتبط --}}
    <div class="bg-yellow-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-yellow-800">مخاطب مرتبط</h3>
            <p class="text-sm text-yellow-600 mt-1">
                {{ optional($opportunity->contact)->name ?? 'مخاطبی برای این فرصت انتخاب نشده است.' }}
            </p>
        </div>
        <i class="fas fa-user-friends text-3xl text-yellow-400"></i>
    </div>

    {{-- پیش‌فاکتورهای مرتبط --}}
    <div class="bg-pink-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-pink-800">پیش‌فاکتورهای مرتبط</h3>
            <p class="text-sm text-pink-600 mt-1">
                تعداد پیش‌فاکتورهای مرتبط: {{ $opportunity->proformas()->count() }}
            </p>
        </div>
        <i class="fas fa-file-invoice text-3xl text-pink-400"></i>
    </div>
</div>

@php
    $roleLabels = [
        'acquirer' => 'جذب‌کننده',
        'relationship_owner' => 'مالک رابطه',
        'temp_relationship_owner' => 'مالک موقت رابطه',
        'closer' => 'کلوزر',
        'pre_sales' => 'پیش‌فروش / فنی',
        'execution_owner' => 'مالک اجرا',
    ];
@endphp

<div class="mt-6 bg-white rounded-lg shadow-sm border p-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-gray-800">نقش‌ها و کمیسیون‌ها</h2>
        <span class="text-xs text-gray-500">{{ $opportunity->roleAssignments->count() }} مورد</span>
    </div>

    @if($opportunity->roleAssignments->isEmpty())
        <div class="text-sm text-gray-500 bg-gray-50 border border-dashed border-gray-200 rounded-md px-4 py-3">
            هنوز نقشی برای این فرصت ثبت نشده است.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-right font-semibold">کاربر</th>
                        <th class="px-4 py-2 text-right font-semibold">نقش</th>
                        <th class="px-4 py-2 text-right font-semibold">سطح</th>
                        <th class="px-4 py-2 text-right font-semibold">درصد پایه</th>
                        <th class="px-4 py-2 text-right font-semibold">مبلغ کمیسیون</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($opportunity->roleAssignments as $assignment)
                        @php
                            $user = $assignment->user;
                            $canLinkUser = \Illuminate\Support\Facades\Route::has('settings.users.edit');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-right text-gray-800">
                                @if($user)
                                    @if($canLinkUser)
                                        <a href="{{ route('settings.users.edit', $user) }}" class="text-blue-600 hover:underline">
                                            {{ $user->name ?? $user->username ?? '—' }}
                                        </a>
                                    @else
                                        <span>{{ $user->name ?? $user->username ?? '—' }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ $roleLabels[$assignment->role_type] ?? $assignment->role_type }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ strtoupper($assignment->level ?? '—') }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                @php $base = $assignment->base_commission_percent; @endphp
                                {{ is_null($base) ? '—' : rtrim(rtrim(number_format((float) $base, 2, '.', ''), '0'), '.') . ' %' }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700 font-semibold">
                                @php $final = $assignment->final_commission_amount; @endphp
                                {{ is_null($final) ? '—' : number_format((float) $final, 2, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
