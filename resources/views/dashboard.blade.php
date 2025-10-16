@extends('layouts.app')

@section('content')
<div class="container py-12" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-8">داشبورد</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- کارت ۲: ایجاد سرنخ --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-green-500 text-4xl mb-3">🧩</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">سرنخ</h3>
                <p class="text-sm text-gray-700 mb-4">مشتریان بالقوه را به سرنخ فروش تبدیل کنید.</p>
                <div class="flex flex-row gap-4">
                <a href="{{ route('marketing.leads.index') }}" class="text-green-600 hover:text-green-800 font-semibold transition">
                     مشاهده سرنخ‌ها
                </a>
                <a href="{{ route('marketing.leads.create') }}" class="text-green-600 hover:text-green-800 font-semibold transition">
                     ایجاد سرنخ جدید
                </a>
                </div>
            </div>

            {{-- کارت ۱: ایجاد فرصت فروش --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-blue-500 text-4xl mb-3">📈</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">فرصت فروش</h3>
                <p class="text-sm text-gray-700 mb-4">فرصت‌های فروش جدیدی را ثبت و پیگیری کنید.</p>
                <div class="flex flex-row gap-4">
                <a href="{{ route('sales.opportunities.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold transition">
                     مشاهده فرصت‌های فروش
                </a>
                <a href="{{ route('sales.opportunities.create') }}"  class="text-blue-600 hover:text-blue-800 font-semibold transition">
                     ایجاد فرصت جدید
                </a>
                </div>
            </div>

            {{-- کارت ۳: اعلانات --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-yellow-500 text-4xl mb-3">🔔</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">اعلانات</h3>
                <p class="text-sm text-gray-700 mb-4">آخرین رویدادها و اعلان‌های مربوط به شما.</p>
                <a href="{{ route('notifications.index') }}" class="text-yellow-600 hover:text-yellow-800 font-semibold transition">
                    مشاهده اعلانات
                </a>
            </div>

            {{-- کارت ۵: پیش‌فاکتور --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-red-500 text-4xl mb-3">🧾</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">پیش‌فاکتورها</h3>
                <p class="text-sm text-gray-700 mb-4">ایجاد یا بررسی پیش‌فاکتورهای صادرشده.</p>
                <div class="flex flex-row gap-4">
                <a href="{{ route('sales.proformas.index') }}" class="text-red-600 hover:text-red-800 font-semibold transition">
                    مشاهده پیش‌فاکتورها
                </a>
                <a href="{{ route('sales.proformas.create') }}" class="text-red-600 hover:text-red-800 font-semibold transition">
                    ایجاد پیش‌فاکتور جدید
                </a>
                </div>
            </div>

            {{-- کارت ۴: مخاطبین --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-purple-500 text-4xl mb-3">👤</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">مخاطبین</h3>
                <p class="text-sm text-gray-700 mb-4">مشاهده و مدیریت لیست مخاطبین ثبت‌شده.</p>
                <div class="flex flex-row gap-4">
                
                <a href="{{ route('sales.contacts.index') }}" class="text-purple-600 hover:text-purple-800 font-semibold transition">
                    مشاهده مخاطبین
                </a>
                <a href="{{ route('sales.contacts.create') }}" class="text-purple-600 hover:text-purple-800 font-semibold transition">
                    ایجاد مخاطب جدید
                </a>
                </div>
            </div>

            {{-- کارت ۶: سازمان‌ها --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-indigo-500 text-4xl mb-3">🏢</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">سازمان‌ها</h3>
                <p class="text-sm text-gray-700 mb-4">مدیریت شرکت‌ها و سازمان‌های ثبت‌شده.</p>
                <div class="flex flex-row gap-4">
                <a href="{{ route('sales.organizations.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                    مشاهده سازمان‌ها
                </a>
                <a href="{{ route('sales.organizations.create') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                    ایجاد سازمان جدید
                </a>
                </div>
                
            </div>
        </div>

        {{-- بخش دو ستونه: وظایف + پیگیری‌های امروز + اعلانات --}}
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-3">

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
