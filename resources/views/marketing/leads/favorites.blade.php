@extends('layouts.app')

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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">سرنخ‌های مورد علاقه</h2>
                <p class="text-sm text-gray-500 mt-1">لیست شخصی از سرنخ‌هایی که برای پیگیری سریع‌تر علامت‌گذاری کرده‌اید.</p>
            </div>
            <a href="{{ route('marketing.leads.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-right ml-2"></i>
                بازگشت به همه سرنخ‌ها
            </a>
        </div>

        @if($leads->count() === 0)
            <div class="bg-white border rounded-lg p-6 text-center text-sm text-gray-500">
                <p>تا الان هیچ سرنخی را به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
                <p class="mt-2">در صفحه سرنخ‌ها روی دکمه «افزودن به علاقه‌مندی» بزنید تا اینجا نمایش داده شوند.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-right">نام کامل</th>
                            <th class="px-2 py-2 text-right">تاریخ ایجاد</th>
                            <th class="px-2 py-2 text-right">موبایل</th>
                            <th class="px-2 py-2 text-right">منبع سرنخ</th>
                            <th class="px-2 py-2 text-right">وضعیت</th>
                            <th class="px-2 py-2 text-right">ارجاع به</th>
                            <th class="px-2 py-2 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($leads as $lead)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-2">
                                    <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-700 hover:underline font-medium">
                                        {{ $lead->full_name ?? '---' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-gray-500">
                                    {{ \Morilog\Jalali\Jalalian::forge($lead->created_at)->format('Y/m/d') }}
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $lead->mobile ?? $lead->phone ?? '---' }}</td>
                                <td class="px-4 py-2 text-gray-500">
                                    {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        $leadStatusColors = [
                                            'new'       => 'bg-blue-100 text-blue-800',
                                            'contacted' => 'bg-amber-100 text-amber-800',
                                            'converted_to_opportunity' => 'bg-green-100 text-green-800',
                                            'converted' => 'bg-green-100 text-green-800',
                                            'discarded' => 'bg-red-100 text-red-800',
                                            'junk'      => 'bg-red-100 text-red-800',
                                        ];
                                        $rawStatus = $lead->status ?? $lead->lead_status;
                                        $statusKey = \App\Models\SalesLead::normalizeStatus($rawStatus) ?? $rawStatus;
                                        $badgeClass = $leadStatusColors[$statusKey] ?? 'bg-gray-100 text-gray-600';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $badgeClass }}">
                                        {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($statusKey) }}
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
                                <td class="px-4 py-2 text-center">
                                    <form method="POST" action="{{ route('marketing.leads.favorites.destroy', $lead) }}" class="inline-flex" onsubmit="return confirm('از لیست علاقه‌مندی حذف شود؟');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="favorites">
                                        <button type="submit" class="text-sm px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200">حذف از لیست</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
