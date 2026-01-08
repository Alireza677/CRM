@extends('layouts.app')

@php
    $directionLabels = [
        'inbound' => 'ورودی',
        'outbound' => 'خروجی',
    ];

    $statusLabels = [
        'queued' => 'در صف',
        'ringing' => 'در حال زنگ خوردن',
        'in-progress' => 'در حال انجام',
        'answered' => 'پاسخ داده شد',
        'completed' => 'تکمیل شده',
        'failed' => 'ناموفق',
        'busy' => 'مشغول',
        'no-answer' => 'بی‌پاسخ',
        'canceled' => 'لغو شده',
        'unknown' => 'نامشخص',
    ];
@endphp

@section('content')
    <div class="py-10 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">گزارش تماس‌های تلفنی</h1>
                    <p class="text-sm text-gray-500 mt-1">لیست کامل تماس‌ها به همراه فیلتر و جستجو</p>
                </div>
                <div>
                    <a href="{{ route('telephony.phone-calls.index') }}"
                       class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        تازه‌سازی
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <form method="GET" action="{{ route('telephony.phone-calls.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm text-gray-600 mb-1 block">جستجو</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}"
                               class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                               placeholder="شماره، نام مشتری یا یادداشت">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1 block">وضعیت تماس</label>
                        <input type="text" name="status" value="{{ $filters['status'] }}"
                               class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                               placeholder="مثلاً پاسخ داده شد">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1 block">جهت تماس</label>
                        <select name="direction"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-white">
                            <option value="">همه</option>
                            @foreach($directionLabels as $key => $label)
                                <option value="{{ $key }}" @selected($filters['direction'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-semibold">
                            جستجو
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-700">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-right">وضعیت تماس</th>
                            <th class="px-4 py-3 text-right">شماره مشتری</th>
                            <th class="px-4 py-3 text-right">مشتری</th>
                            <th class="px-4 py-3 text-right">یادداشت‌های تماس</th>
                            <th class="px-4 py-3 text-right">اداره شده توسط</th>
                            <th class="px-4 py-3 text-right">شناسه منبع</th>
                            <th class="px-4 py-3 text-right">زمان شروع</th>
                            <th class="px-4 py-3 text-right">جهت تماس</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($phoneCalls as $call)
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $statusLabels[$call->status] ?? $call->status }}
                                </td>
                                <td class="px-4 py-3">{{ $call->customer_number }}</td>
                                <td class="px-4 py-3">
                                    {{ $call->customer_name ?? $call->customer?->name ?? 'بدون نام' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $call->notes ? \Illuminate\Support\Str::limit($call->notes, 70) : '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $call->handledBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $call->source_identifier ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    {{ $call->started_at ? \App\Helpers\DateHelper::toJalali($call->started_at, 'H:i Y/m/d') : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $directionLabels[$call->direction] ?? $call->direction }}
                                </td>
                                <td class="px-4 py-3 text-left">
                                    <a href="{{ route('telephony.phone-calls.show', $call) }}"
                                       class="text-blue-600 hover:text-blue-800 text-xs font-semibold">مشاهده</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    هیچ رکوردی پیدا نشد. یک رکورد جدید ایجاد کنید یا جستجوی خود را تغییر دهید.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $phoneCalls->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
