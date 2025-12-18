@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => __('Settings')],
        ['title' => __('Lead Round Robin')],
    ];

    $displayUser = function ($user) {
        $name = $user->name ?? $user->full_name ?? $user->username ?? ('User #' . $user->id);
        $extras = array_filter([$user->mobile ?? null, $user->email ?? null]);
        return $extras ? $name . ' (' . implode(' / ', $extras) . ')' : $name;
    };
@endphp

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg border border-gray-200">
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-800">مدیریت چرخش سرنخ ها بین کاربران</h1>
            <p class="mt-1 text-sm text-gray-600">کاربران فعال در توزیع خودکار سرنخ‌ها را اینجا مدیریت کنید.</p>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">تنظیمات چرخش  و جابه‌جایی خودکار</h2>
                <form action="{{ route('settings.automation.leads.roundrobin.settings') }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="sla_duration_value" class="block text-sm font-medium text-gray-700 mb-1">مدت نگهداری هر سرنخ برای کاربر</label>
                            <input type="number" min="1" name="sla_duration_value" id="sla_duration_value" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" value="{{ old('sla_duration_value', $settings->sla_duration_value) }}" required>
                        </div>
                        <div>
                            <label for="sla_duration_unit" class="block text-sm font-medium text-gray-700 mb-1">واحد زمان</label>
                            <select name="sla_duration_unit" id="sla_duration_unit" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="minutes" @selected(old('sla_duration_unit', $settings->sla_duration_unit) === 'minutes')>دقیقه</option>
                                <option value="hours" @selected(old('sla_duration_unit', $settings->sla_duration_unit) === 'hours')>ساعت</option>
                            </select>
                        </div>
                        <div>
                            <label for="max_reassign_count" class="block text-sm font-medium text-gray-700 mb-1">حداکثر تعداد جابه‌جایی خودکار</label>
                            <input type="number" min="0" name="max_reassign_count" id="max_reassign_count" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" value="{{ old('max_reassign_count', $settings->max_reassign_count) }}" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <input type="hidden" name="enable_rotation_warning" value="0">
                            <input type="checkbox" name="enable_rotation_warning" id="enable_rotation_warning" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded" @checked(old('enable_rotation_warning', $settings->enable_rotation_warning))>
                            <label for="enable_rotation_warning" class="text-sm font-medium text-gray-700">ارسال اخطار قبل از جابه‌جایی</label>
                        </div>
                        <div>
                            <label for="rotation_warning_time" class="block text-sm font-medium text-gray-700 mb-1">زمان اخطار پیش از جابه‌جایی</label>
                            <input type="number" min="1" name="rotation_warning_time" id="rotation_warning_time" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" value="{{ old('rotation_warning_time', $settings->rotation_warning_time) }}" required>
                        </div>
                        <div>
                            <label for="rotation_warning_unit" class="block text-sm font-medium text-gray-700 mb-1">واحد اخطار</label>
                            <select name="rotation_warning_unit" id="rotation_warning_unit" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="hours" @selected(old('rotation_warning_unit', $settings->rotation_warning_unit) === 'hours')>ساعت</option>
                                <option value="days" @selected(old('rotation_warning_unit', $settings->rotation_warning_unit) === 'days')>روز</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('settings.automation.leads.roundrobin.store') }}" method="POST" class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-4">
                @csrf
                <div class="flex flex-col md:flex-row md:items-end md:space-x-4 md:space-x-reverse space-y-3 md:space-y-0">
                    <div class="flex-1">
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">افزودن کاربر جدید</label>
                        <select id="user_id" name="user_id" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" @disabled($availableUsers->isEmpty())>
                            <option value="">یک کاربر انتخاب کنید</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $displayUser($user) }}</option>
                            @endforeach
                        </select>
                        @if($availableUsers->isEmpty())
                            <p class="mt-1 text-xs text-gray-500">همه کاربران موجود در Round-Robin هستند.</p>
                        @endif
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60" @disabled($availableUsers->isEmpty())>
                            افزودن
                        </button>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">نام کاربر</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">وضعیت</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">آخرین تخصیص</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rows as $row)
                            <tr class="bg-white">
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $row->user ? $displayUser($row->user) : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($row->is_active)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $row->last_assigned_at ? $row->last_assigned_at->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3 space-x-2 space-x-reverse">
                                    <form action="{{ route('settings.automation.leads.roundrobin.toggle', $row) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100">
                                            {{ $row->is_active ? 'غیرفعال' : 'فعال' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('settings.automation.leads.roundrobin.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('حذف شود؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100">
                                            حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-600">هنوز کاربری به Round-Robin اضافه نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
