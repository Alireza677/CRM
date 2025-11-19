@extends('layouts.app')

@php
    $favoriteLeadIds = $favoriteLeadIds ?? [];
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
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">سرنخ‌های تبدیل‌شده</h2>
                <p class="text-sm text-gray-500 mt-1">همه سرنخ‌هایی که به فرصت فروش تبدیل شده‌اند در این لیست نمایش داده می‌شوند.</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('marketing.leads.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border rounded-md shadow-sm text-sm text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-list ml-2"></i>
                    بازگشت به سرنخ‌ها
                </a>
                <a href="{{ route('marketing.leads.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md shadow hover:bg-blue-700 text-sm">
                    <i class="fas fa-plus ml-2"></i>
                    ایجاد سرنخ جدید
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('marketing.leads.converted') }}" class="mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                        <tr>
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
                                    <a href="{{ route('marketing.leads.converted') }}" class="bg-gray-300 px-3 py-1 rounded text-sm">پاکسازی</a>
                                </div>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </form>

        <div class="flex items-center justify-end mb-4">
            <form method="GET" action="{{ route('marketing.leads.converted') }}" class="flex items-center gap-2 text-sm">
                <label for="converted-per-page" class="text-gray-700 whitespace-nowrap">تعداد نمایش:</label>
                <select id="converted-per-page"
                        name="per_page"
                        class="border rounded-md px-2 py-1 focus:outline-none focus:ring"
                        onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
                @foreach(request()->except('per_page', 'page') as $name => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-right">نام کامل</th>
                        <th class="px-4 py-2 text-right">تاریخ تبدیل</th>
                        <th class="px-4 py-2 text-right">موبایل</th>
                        <th class="px-4 py-2 text-right">منبع سرنخ</th>
                        <th class="px-4 py-2 text-right">وضعیت</th>
                        <th class="px-4 py-2 text-right">ارجاع به</th>
                        <th class="px-4 py-2 text-right">فرصت ایجاد شده</th>
                        <th class="px-4 py-2 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2">
                                <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-700 hover:underline">
                                    {{ $lead->full_name ?? '---' }}
                                </a>
                                <span class="ml-2 px-2 py-0.5 text-[10px] rounded-full bg-green-100 text-green-800 align-middle">تبدیل شده</span>
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $lead->converted_at ? \Morilog\Jalali\Jalalian::forge($lead->converted_at)->format('Y/m/d') : '---' }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $lead->mobile ?? $lead->phone ?? '---' }}</td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 inline-flex text-xs font-semibold rounded-full
                                    @if($lead->lead_status === 'new') bg-blue-100 text-blue-800
                                    @elseif($lead->lead_status === 'contacted') bg-yellow-100 text-yellow-800
                                    @elseif($lead->lead_status === 'qualified') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($lead->lead_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                @if($lead->assignedUser)
                                    {{ $lead->assignedUser->name }}
                                @elseif($lead->assigned_to)
                                    (کاربر حذف شده) [ID: {{ $lead->assigned_to }}]
                                @else
                                    بدون مسئول
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                @if($lead->convertedOpportunity)
                                    <a href="{{ route('sales.opportunities.show', $lead->convertedOpportunity) }}"
                                       class="text-indigo-600 hover:underline text-xs">
                                        {{ $lead->convertedOpportunity->name }}
                                    </a>
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                @php
                                    $isFavorite = in_array($lead->id, $favoriteLeadIds);
                                    $favoriteFormId = 'favorite-converted-toggle-' . $lead->id;
                                @endphp
                                <div class="flex items-center gap-3 justify-center">
                                    <button
                                        type="submit"
                                        form="{{ $favoriteFormId }}"
                                        class="inline-flex items-center text-xs px-2 py-1 rounded {{ $isFavorite ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                                        aria-label="{{ $isFavorite ? 'حذف از علاقه‌مندی' : 'افزودن به علاقه‌مندی' }}">
                                        <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-star ml-1"></i>
                                    </button>
                                    <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-500 hover:underline text-xs">مشاهده سرنخ</a>
                                    @if($lead->convertedOpportunity)
                                        <a href="{{ route('sales.opportunities.show', $lead->convertedOpportunity) }}"
                                           class="text-indigo-600 hover:underline text-xs">
                                            مشاهده فرصت
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">
                                هیچ سرنخ تبدیل‌شده‌ای برای نمایش وجود ندارد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leads->links() }}
        </div>
    </div>
</div>

@foreach($leads as $lead)
    @php
        $isFavorite = in_array($lead->id, $favoriteLeadIds);
    @endphp
    <form id="favorite-converted-toggle-{{ $lead->id }}" method="POST" action="{{ $isFavorite ? route('marketing.leads.favorites.destroy', $lead) : route('marketing.leads.favorites.store', $lead) }}" style="display:none">
        @csrf
        @if($isFavorite)
            @method('DELETE')
        @endif
    </form>
@endforeach
@endsection
