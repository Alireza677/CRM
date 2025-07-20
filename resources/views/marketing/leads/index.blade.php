@extends('layouts.app')

@php
    $breadcrumb = [['title' => 'سرنخ‌های فروش']];
@endphp

@section('content')
@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

<div class="py-12">
    <div class="px-4">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">سرنخ‌های فروش</h2>

        <!-- فرم جستجو -->
        <form method="GET" action="{{ route('marketing.leads.index') }}" class="mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <!-- ردیف عنوان -->
                        <tr>
                            <th></th>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                        <!-- ردیف فیلتر -->
                        <tr>
                            <th></th>
                            <th>
                                <input type="text" name="full_name" value="{{ request('full_name') }}" placeholder="نام کامل"
                                    class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th>
                                <input type="text" name="mobile" value="{{ request('mobile') }}" placeholder="موبایل"
                                       class="border rounded-md p-1 w-full text-sm">
                            </th>
                            <th>
                                <select name="lead_source" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه منابع</option>
                                    @foreach($leadSources as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_source') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th>
                                <select name="lead_status" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه</option>
                                    <option value="new" {{ request('lead_status') == 'new' ? 'selected' : '' }}>جدید</option>
                                    <option value="contacted" {{ request('lead_status') == 'contacted' ? 'selected' : '' }}>تماس گرفته شده</option>
                                    <option value="qualified" {{ request('lead_status') == 'qualified' ? 'selected' : '' }}>در حال پیگیری</option>
                                </select>
                            </th>
                            <th>
                                <select name="assigned_to" class="border rounded-md p-1 w-full text-sm">
                                    <option value="">همه</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="text-center">
                                <div class="flex gap-2 justify-center">
                                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">جستجو</button>
                                    <a href="{{ route('marketing.leads.index') }}" class="bg-gray-300 px-3 py-1 rounded text-sm">پاکسازی</a>
                                </div>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </form>

        <!-- فرم حذف گروهی + جدول -->
        <form method="POST" action="{{ route('marketing.leads.bulk-delete') }}" onsubmit="return confirm('آیا مطمئنید؟')">
            @csrf

            <div class="flex justify-between items-center mb-4">
                <div></div>
                <div class="flex gap-2">
                    <a href="{{ route('marketing.leads.create') }}"
                    class="group relative"
                    title="ایجاد سرنخ جدید">ایجاد سرنخ
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600 hover:text-blue-800"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="absolute bottom-full right-0 mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2">
                            ایجاد سرنخ
                        </span>
                    </a>
                      <button type="submit" title="حذف انتخاب‌شده‌ها" class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600 hover:text-red-800"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4" />
                        </svg>
                        <span class="absolute bottom-full right-0 mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2">
                            حذف انتخاب‌شده‌ها
                        </span>
                    </button> 
                </div>
            </div>

            <!-- جدول -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-center">
                                <input type="checkbox" id="select-all" class="form-checkbox">
                            </th>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-2 py-2 text-center">
                                <input type="checkbox" name="selected_leads[]" value="{{ $lead->id }}" class="form-checkbox row-checkbox">
                            </td>
                            <td class="px-6 py-2 text-sm text-blue-700 hover:underline">
                                <a href="{{ route('marketing.leads.show', $lead) }}">
                                    {{ $lead->full_name }}
                                </a>
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500">{{ $lead->mobile ?? $lead->phone }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500">
                                {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
                            </td>
                            <td class="px-6 py-2">
                                <span class="px-2 inline-flex text-xs font-semibold rounded-full
                                    @if($lead->lead_status === 'new') bg-blue-100 text-blue-800
                                    @elseif($lead->lead_status === 'contacted') bg-yellow-100 text-yellow-800
                                    @elseif($lead->lead_status === 'qualified') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($lead->lead_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500">
                                @if($lead->assignedUser)
                                    {{ $lead->assignedUser->name }}
                                @elseif($lead->assigned_to)
                                    (کاربر حذف شده) [ID: {{ $lead->assigned_to }}]
                                @else
                                    بدون مسئول
                                @endif
                            </td>
                            <td class="px-6 py-2 text-center">
                                <a href="{{ route('marketing.leads.edit', $lead) }}" class="text-blue-500 hover:underline">ویرایش</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">هیچ سرنخی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $leads->links() }}
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('select-all').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection
