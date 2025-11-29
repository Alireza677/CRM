@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'جلسات آنلاین', 'url' => route('sales.online-meetings.index')],
    ];
    $relatedLabels = [
        \App\Models\Opportunity::class => 'فرصت فروش',
        \App\Models\Contact::class => 'مخاطب',
        \App\Models\Organization::class => 'سازمان',
    ];
@endphp

<div class="max-w-7xl mx-auto p-4" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">جلسات آنلاین</h1>
            <p class="text-sm text-gray-500 mt-1">مدیریت اطلاعات جلسه و لینک خودکار Jitsi</p>
        </div>
        <a href="{{ route('sales.online-meetings.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
            <i class="fa fa-plus ml-2"></i>
            <span>جلسه جدید</span>
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold">عنوان</th>
                    <th class="px-4 py-3 text-right font-semibold">ارتباط</th>
                    <th class="px-4 py-3 text-right font-semibold">زمان برگزاری</th>
                    <th class="px-4 py-3 text-right font-semibold">مدت (دقیقه)</th>
                    <th class="px-4 py-3 text-right font-semibold">لینک Jitsi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($meetings as $meeting)
                    @php
                        $relatedName = optional($meeting->related)->name ?? optional($meeting->related)->title;
                        $relatedType = $relatedLabels[$meeting->related_type] ?? '---';
                    @endphp
                    <tr class="border-t">
                        <td class="px-4 py-3">
                            <a href="{{ route('sales.online-meetings.show', $meeting) }}"
                               class="text-blue-600 hover:underline font-semibold">
                                {{ $meeting->title }}
                            </a>
                            <div class="text-xs text-gray-500 mt-1 font-mono">ROOM: {{ $meeting->room_name }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-800">{{ $relatedType }}</div>
                            <div class="text-xs text-gray-500">{{ $relatedName ?: '---' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ optional($meeting->scheduled_at)->format('Y-m-d H:i') ?? '---' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $meeting->duration_minutes ? $meeting->duration_minutes . ' دقیقه' : '---' }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ $meeting->jitsi_url }}" target="_blank" rel="noopener"
                               class="text-green-600 hover:underline">
                                ورود به جلسه
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            هیچ جلسه آنلاینی ثبت نشده است.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($meetings, 'links'))
        <div class="mt-4">
            {{ $meetings->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
