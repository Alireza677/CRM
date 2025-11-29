@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'جلسات آنلاین', 'url' => route('sales.online-meetings.index')],
        ['title' => $onlineMeeting->title],
    ];
    $relatedLabels = [
        \App\Models\Opportunity::class => 'فرصت فروش',
        \App\Models\Contact::class => 'مخاطب',
        \App\Models\Organization::class => 'سازمان',
    ];
    $relatedName = optional($onlineMeeting->related)->name ?? optional($onlineMeeting->related)->title;
@endphp

<div class="max-w-4xl mx-auto p-4" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">{{ $onlineMeeting->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">ROOM: {{ $onlineMeeting->room_name }}</p>
        </div>
        <a href="{{ $onlineMeeting->jitsi_url }}" target="_blank" rel="noopener"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 shadow-sm">
            <i class="fa fa-video ml-2"></i>
            <span>ورود به جلسه (Jitsi)</span>
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-xl divide-y">
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-800">
            <div>
                <div class="text-gray-500 mb-1">زمان برگزاری</div>
                <div class="font-semibold">{{ optional($onlineMeeting->scheduled_at)->format('Y-m-d H:i') ?? '---' }}</div>
            </div>
            <div>
                <div class="text-gray-500 mb-1">مدت جلسه</div>
                <div class="font-semibold">{{ $onlineMeeting->duration_minutes ? $onlineMeeting->duration_minutes . ' دقیقه' : '---' }}</div>
            </div>
            <div>
                <div class="text-gray-500 mb-1">ارتباط</div>
                <div class="font-semibold">{{ $relatedLabels[$onlineMeeting->related_type] ?? '---' }}</div>
                <div class="text-gray-500 mt-1">{{ $relatedName ?: '' }}</div>
            </div>
            <div>
                <div class="text-gray-500 mb-1">لینک جلسه</div>
                <a href="{{ $onlineMeeting->jitsi_url }}" target="_blank" rel="noopener"
                   class="font-semibold text-blue-600 hover:underline break-all">{{ $onlineMeeting->jitsi_url }}</a>
            </div>
        </div>

        <div class="p-6 text-sm">
            <div class="text-gray-500 mb-2">یادداشت</div>
            <div class="text-gray-800 leading-relaxed whitespace-pre-line">
                {{ $onlineMeeting->notes ?: 'یادداشتی ثبت نشده است.' }}
            </div>
        </div>
    </div>
</div>
@endsection
