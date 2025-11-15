@extends('layouts.app')

@section('content')
<div class="w-[90%] mx-auto py-12" dir="rtl">
    <div class=" mx-auto sm:px-6 lg:px-8">
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
     {{-- کارت ۲: سرنخ --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md">
        <div class="absolute -top-4 flex justify-center">
            <img src="{{ asset('images/recruitment.png') }}" alt="icon" class="w-12 h-12 object-contain drop-shadow">
        </div>
        <h3 class="text-sm font-bold text-gray-800 mb-1 mt-4">سرنخ</h3>
        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
            مشتریان بالقوه را به سرنخ فروش تبدیل کنید.
        </p>
        <div class="flex flex-row gap-2 text-xs">
            <a href="{{ route('marketing.leads.index') }}" class="text-green-600 hover:text-indigo-800 font-semibold transition">
                مشاهده
            </a>
            <a href="{{ route('marketing.leads.create') }}" class="text-green-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </a>
        </div>
    </div>
    {{-- کارت ۱: فرصت فروش --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md">
        <div class="absolute -top-4 flex justify-center">
            <img src="{{ asset('images/sales-pipeline.png') }}" alt="icon" class="w-12 h-12 object-contain drop-shadow">
        </div>
        <h3 class="text-sm font-bold text-gray-800 mb-1 mt-4">فرصت فروش</h3>
        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
            فرصت‌ فروش جدیدی را ثبت و پیگیری کنید.
        </p>
        <div class="flex flex-row gap-2 text-xs">
            <a href="{{ route('sales.opportunities.index') }}" class="text-blue-600 hover:text-indigo-800 font-semibold transition">
                مشاهده
            </a>
            <a href="{{ route('sales.opportunities.create') }}" class="text-blue-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </a>
        </div>
    </div>

    {{-- کارت ۴: پیش‌فاکتور --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md">
        <div class="absolute -top-4 flex justify-center">
            <img src="{{ asset('images/invoice.png') }}" alt="icon" class="w-12 h-12 object-contain drop-shadow">
        </div>
        <h3 class="text-sm font-bold text-gray-800 mb-1 mt-4">پیش‌فاکتورها</h3>
        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
            ایجاد یا بررسی پیش‌فاکتورهای صادرشده.
        </p>
        <div class="flex flex-row gap-2 text-xs">
            <a href="{{ route('sales.proformas.index') }}" class="text-red-600 hover:text-indigo-800 font-semibold transition">
                مشاهده
            </a>
            <a href="{{ route('sales.proformas.create') }}" class="text-red-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </a>
        </div>
    </div>

    {{-- کارت ۵: سفارش‌های خرید --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md">
        <div class="absolute -top-4 flex justify-center">
            <img src="{{ asset('images/invoice.png') }}" alt="icon" class="w-12 h-12 object-contain drop-shadow">
        </div>
        <h3 class="text-sm font-bold text-gray-800 mb-1 mt-4">سفارش‌های خرید</h3>
        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
            ثبت، مدیریت و پیگیری سفارش‌های خرید.
        </p>
        <div class="flex flex-row gap-2 text-xs">
            <a href="{{ route('inventory.purchase-orders.index') }}" class="text-orange-600 hover:text-indigo-800 font-semibold transition">
                مشاهده
            </a>
            <a href="{{ route('inventory.purchase-orders.create') }}" class="text-orange-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </a>
        </div>
    </div>

    
    {{-- کارت ۳: مخاطبین --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md">
        <div class="absolute -top-4 flex justify-center">
            <img src="{{ asset('images/contacts-book.png') }}" alt="icon" class="w-12 h-12 object-contain drop-shadow">
        </div>
        <h3 class="text-sm font-bold text-gray-800 mb-1 mt-4">مخاطبین</h3>
        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
            مشاهده و مدیریت لیست مخاطبین ثبت‌شده.
        </p>
        <div class="flex flex-row gap-2 text-xs">
            <a href="{{ route('sales.contacts.index') }}" class="text-purple-600 hover:text-indigo-800 font-semibold transition">
                مشاهده
            </a>
            <a href="{{ route('sales.contacts.create') }}" class="text-purple-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </a>
        </div>
    </div>

    {{-- کارت ۶: سازمان‌ها --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md">
        <div class="absolute -top-4 flex justify-center">
            <img src="{{ asset('images/organization.png') }}" alt="icon" class="w-12 h-12 object-contain drop-shadow">
        </div>
        <h3 class="text-sm font-bold text-gray-800 mb-1 mt-4">سازمان‌ها</h3>
        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
            مدیریت شرکت‌ها و سازمان‌های ثبت‌شده.
        </p>
        <div class="flex flex-row gap-2 text-xs">
            <a href="{{ route('sales.organizations.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                مشاهده
            </a>
            <a href="{{ route('sales.organizations.create') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </a>
        </div>
    </div>

</div>


        {{-- بخش دو ستونه: وظایف + پیگیری‌های امروز + اعلانات --}}
<div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-3 max-w-6xl mx-auto">

            {{-- باکس وظایف تکمیل‌نشده --}}
            <div class="w-full max-w-[400px] h-[400px] bg-white border border-gray-200 shadow rounded-none flex flex-col">
                <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">وظایف تکمیل‌نشده</h3>
                    <a href="{{ route('activities.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده همه</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if($tasks->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">وظیفه‌ای وجود ندارد.</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($tasks as $task)
                                <li>
                                    <a href="{{ route('activities.show', $task->id) }}"
                                       class="block px-4 py-2 hover:bg-gray-50 focus:bg-gray-50 transition outline-none"
                                       aria-label="مشاهده وظیفه">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[13px] text-gray-700 truncate">
                                                    {{ $task->subject ?? 'بدون عنوان' }}
                                                </p>
                                                <span class="text-[11px] text-gray-400">
                                                    موعد: {{ $task->due_at?->diffForHumans() ?? '---' }}
                                                </span>
                                            </div>
                                            <span class="mt-0.5 inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-800">
                                                در انتظار
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="p-3 border-t border-gray-200">
                    <a href="{{ route('activities.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        مشاهده همه وظایف
                    </a>
                </div>
            </div>

            {{-- باکس پیگیری‌های امروز --}}
            <div class="w-full max-w-[400px] h-[400px] bg-white border border-gray-200 shadow rounded-none flex flex-col">
                <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">پیگیری‌های امروز</h3>
                    <a href="{{ route('calendar.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده تقویم</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if(($todayFollowUps ?? collect())->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">برای امروز پیگیری برنامه‌ریزی نشده است.</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($todayFollowUps as $fu)
                                <li>
                                    <a href="{{ $fu['url'] ?? '#' }}" class="block px-4 py-2 hover:bg-gray-50 focus:bg-gray-50 transition outline-none">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[13px] text-gray-700 truncate">{{ $fu['title'] }}</p>
                                                <span class="text-[11px] text-gray-400">
                                                    {{ \Carbon\Carbon::parse($fu['date'])->diffForHumans() }}
                                                </span>
                                            </div>
                                            <span class="mt-0.5 inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded {{ ($fu['type'] ?? '') === 'lead' ? 'bg-pink-100 text-pink-800' : 'bg-indigo-100 text-indigo-800' }}">
                                                {{ ($fu['type'] ?? '') === 'lead' ? 'سرنخ' : 'فرصت' }}
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="p-3 border-t border-gray-200">
                    <a href="{{ route('calendar.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        رفتن به تقویم
                    </a>
                </div>
            </div>

            {{-- باکس آخرین اعلانات --}}
            <div class="w-full max-w-[400px] h-[400px] bg-white border border-gray-200 shadow rounded-none flex flex-col">
                <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">آخرین اعلانات</h3>
                    <a href="{{ route('notifications.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده همه</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if($notifications->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">اعلانی وجود ندارد.</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($notifications as $notification)
                                @php
                                    $data = $notification->data ?? [];
                                    $itemUrl = $data['url']
                                        ?? (isset($data['route']) && \Illuminate\Support\Facades\Route::has($data['route'])
                                            ? route($data['route'], $data['params'] ?? [])
                                            : ( \Illuminate\Support\Facades\Route::has('notifications.index')
                                                ? route('notifications.index')
                                                : '#' ));
                                    $title = $data['message'] ?? $data['title'] ?? 'اعلان جدید';
                                @endphp
                                @php
                                    // Prefer template-based title over legacy message
                                    $title = $data['title'] ?? $data['message'] ?? $title;
                                @endphp

                                <li>
                                    <a href="{{ $itemUrl }}" class="block px-4 py-2 hover:bg-gray-50 focus:bg-gray-50 transition outline-none" aria-label="مشاهده اعلان">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[13px] text-gray-700 truncate">{{ $title }}</p>
                                                <span class="text-[11px] text-gray-400">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </span>
                                            </div>

                                            @if(is_null($notification->read_at))
                                                <span class="mt-0.5 inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-600"></span> جدید
                                                </span>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="p-3 border-t border-gray-200">
                    <a href="{{ route('notifications.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        مشاهده همه اعلانات
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
