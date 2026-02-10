@extends('layouts.app')

@section('content')
<div class="w-[90%] mx-auto py-12" dir="rtl">
    <div class=" mx-auto sm:px-6 lg:px-8">
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
     {{-- کارت ۲: سرنخ --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md" data-report-card>
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
            <button id="openLeadModal"
                    type="button"
                    class="text-green-600 hover:text-indigo-800 font-semibold transition">
                + ایجاد
            </button>
        </div>
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 transition" data-report-toggle aria-expanded="false">
            <span>مشاهده گزارش</span>
            <span class="text-[10px] transition-transform" data-report-icon>&#9662;</span>
        </button>
        <div class="mt-3 w-full hidden" data-report-panel>
            <div class="h-32 overflow-y-auto w-full bg-gray-50/80 border border-green-100 rounded-lg px-3 py-2 space-y-1 text-right">
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>جدید (۳۰ روز اخیر)</span>
                    <span class="font-semibold text-gray-900">{{ $leadStats['new'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>در انتظار اقدام</span>
                    <span class="font-semibold text-gray-900">{{ $leadStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>تبدیل‌شده</span>
                    <span class="font-semibold text-gray-900">{{ $leadStats['converted'] ?? 0 }}</span>
                </div>
                
                @if(!empty($leadStats['statuses']))
                    <div class="pt-1 mt-1 border-t border-dashed border-gray-200 space-y-0.5">
                        @foreach($leadStats['statuses'] as $status)
                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                <span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- کارت ۱: فرصت فروش --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md" data-report-card>
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
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 transition" data-report-toggle aria-expanded="false">
            <span>مشاهده گزارش</span>
            <span class="text-[10px] transition-transform" data-report-icon>&#9662;</span>
        </button>
        <div class="mt-3 w-full hidden" data-report-panel>
            <div class="h-32 overflow-y-auto w-full bg-gray-50/80 border border-blue-100 rounded-lg px-3 py-2 space-y-1 text-right">
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>جدید (۳۰ روز اخیر)</span>
                    <span class="font-semibold text-gray-900">{{ $opportunityStats['new'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>در انتظار اقدام</span>
                    <span class="font-semibold text-gray-900">{{ $opportunityStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>تبدیل‌شده</span>
                    <span class="font-semibold text-gray-900">{{ $opportunityStats['converted'] ?? 0 }}</span>
                </div>
               
                @if(!empty($opportunityStats['statuses']))
                    <div class="pt-1 mt-1 border-t border-dashed border-gray-200 space-y-0.5">
                        @foreach($opportunityStats['statuses'] as $status)
                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                <span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- کارت ۴: پیش‌فاکتور --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md" data-report-card>
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
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 transition" data-report-toggle aria-expanded="false">
            <span>مشاهده گزارش</span>
            <span class="text-[10px] transition-transform" data-report-icon>&#9662;</span>
        </button>
        <div class="mt-3 w-full hidden" data-report-panel>
            <div class="h-32 overflow-y-auto w-full bg-gray-50/80 border border-red-100 rounded-lg px-3 py-2 space-y-1 text-right">
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>جدید (۳۰ روز اخیر)</span>
                    <span class="font-semibold text-gray-900">{{ $proformaStats['new'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>در انتظار اقدام</span>
                    <span class="font-semibold text-gray-900">{{ $proformaStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>تبدیل‌شده</span>
                    <span class="font-semibold text-gray-900">{{ $proformaStats['converted'] ?? 0 }}</span>
                </div>
                
                @if(!empty($proformaStats['statuses']))
                    <div class="pt-1 mt-1 border-t border-dashed border-gray-200 space-y-0.5">
                        @foreach($proformaStats['statuses'] as $status)
                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                <span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- کارت ۵: سفارش‌های خرید --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md" data-report-card>
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
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 transition" data-report-toggle aria-expanded="false">
            <span>مشاهده گزارش</span>
            <span class="text-[10px] transition-transform" data-report-icon>&#9662;</span>
        </button>
        <div class="mt-3 w-full hidden" data-report-panel>
            <div class="h-32 overflow-y-auto w-full bg-gray-50/80 border border-orange-100 rounded-lg px-3 py-2 space-y-1 text-right">
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>جدید (۳۰ روز اخیر)</span>
                    <span class="font-semibold text-gray-900">{{ $purchaseOrderStats['new'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>در انتظار اقدام</span>
                    <span class="font-semibold text-gray-900">{{ $purchaseOrderStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>تبدیل‌شده</span>
                    <span class="font-semibold text-gray-900">{{ $purchaseOrderStats['converted'] ?? 0 }}</span>
                </div>
                
                @if(!empty($purchaseOrderStats['statuses']))
                    <div class="pt-1 mt-1 border-t border-dashed border-gray-200 space-y-0.5">
                        @foreach($purchaseOrderStats['statuses'] as $status)
                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                <span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    
    {{-- کارت ۳: مخاطبین --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md" data-report-card>
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
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 transition" data-report-toggle aria-expanded="false">
            <span>مشاهده گزارش</span>
            <span class="text-[10px] transition-transform" data-report-icon>&#9662;</span>
        </button>
        <div class="mt-3 w-full hidden" data-report-panel>
            <div class="h-32 overflow-y-auto w-full bg-gray-50/80 border border-purple-100 rounded-lg px-3 py-2 space-y-1 text-right">
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>جدید (۳۰ روز اخیر)</span>
                    <span class="font-semibold text-gray-900">{{ $contactStats['new'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>در انتظار اقدام</span>
                    <span class="font-semibold text-gray-900">{{ $contactStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>تبدیل‌شده</span>
                    <span class="font-semibold text-gray-900">{{ $contactStats['converted'] ?? 0 }}</span>
                </div>
                
                @if(!empty($contactStats['statuses']))
                    <div class="pt-1 mt-1 border-t border-dashed border-gray-200 space-y-0.5">
                        @foreach($contactStats['statuses'] as $status)
                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                <span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- کارت ۶: سازمان‌ها --}}
    <div class="relative backdrop-blur-md bg-white/70 border border-white/40 shadow-sm rounded-xl px-3 pt-3 pb-2 flex flex-col items-center text-center transition hover:shadow-md" data-report-card>
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
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 transition" data-report-toggle aria-expanded="false">
            <span>مشاهده گزارش</span>
            <span class="text-[10px] transition-transform" data-report-icon>&#9662;</span>
        </button>
        <div class="mt-3 w-full hidden" data-report-panel>
            <div class="h-32 overflow-y-auto w-full bg-gray-50/80 border border-indigo-100 rounded-lg px-3 py-2 space-y-1 text-right">
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>جدید (۳۰ روز اخیر)</span>
                    <span class="font-semibold text-gray-900">{{ $organizationStats['new'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>در انتظار اقدام</span>
                    <span class="font-semibold text-gray-900">{{ $organizationStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-700">
                    <span>تبدیل‌شده</span>
                    <span class="font-semibold text-gray-900">{{ $organizationStats['converted'] ?? 0 }}</span>
                </div>
                
                @if(!empty($organizationStats['statuses']))
                    <div class="pt-1 mt-1 border-t border-dashed border-gray-200 space-y-0.5">
                        @foreach($organizationStats['statuses'] as $status)
                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                <span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>


<!-- Lead create choice modal -->
<div id="leadChoiceModal" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/40 lead-modal-overlay"></div>
    <div class="relative bg-white rounded-xl shadow-lg w-full max-w-md mx-auto p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-gray-800">ایجاد سرنخ</h3>
                <p class="text-sm text-gray-600 mt-1">انتخاب کنید برای چه نوع مخاطبی می‌خواهید سرنخ بسازید.</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-close-lead-modal aria-label="بستن">
                &#10005;
            </button>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('sales.contacts.create') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-green-600 text-green-700 px-4 py-2 text-sm font-semibold hover:bg-green-50 transition">
                سرنخ برای مخاطب جدید
                
            </a>
            <a href="{{ route('marketing.leads.create') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 text-white px-4 py-2 text-sm font-semibold hover:bg-green-700 transition">
                سرنخ برای مخاطب موجود
            </a>
        </div>
        <div class="flex justify-end">
            <button type="button" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition" data-close-lead-modal>
                انصراف
            </button>
        </div>
    </div>
</div>

        {{-- بخش سه ستونه: وظایف + پیگیری‌های امروز + پیگیری‌های گذشته + اعلانات --}}
<div class="mt-10 grid grid-cols-1 md:grid-cols-4 gap-4 max-w-7xl mx-auto">

            {{-- باکس وظایف تکمیل‌نشده --}}
            <div class="w-full max-w-[400px] h-[400px] bg-gradient-to-br from-white via-white to-blue-50/40 border border-gray-200/70 shadow-sm rounded-xl flex flex-col overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200/60 flex items-center justify-between bg-white/70 backdrop-blur">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">وظایف تکمیل‌نشده</h3>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">{{ $tasks->count() }}</span>
                    </div>
                    <a href="{{ route('activities.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده همه</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if($tasks->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">وظیفه‌ای وجود ندارد.</p>
                    @else
                        <ul class="divide-y divide-gray-100/70">
                            @foreach($tasks as $task)
                                <li>
                                    <a href="{{ route('activities.show', $task->id) }}"
                                       class="block px-4 py-2 hover:bg-white/70 focus:bg-white/70 transition outline-none"
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

                <div class="p-3 border-t border-gray-200/60 bg-white/70">
                    <a href="{{ route('activities.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        مشاهده همه وظایف
                    </a>
                </div>
            </div>

            {{-- باکس پیگیری‌های امروز --}}
            <div class="w-full max-w-[400px] h-[400px] bg-gradient-to-br from-white via-white to-indigo-50/40 border border-gray-200/70 shadow-sm rounded-xl flex flex-col overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200/60 flex items-center justify-between bg-white/70 backdrop-blur">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">پیگیری‌های امروز</h3>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">{{ ($todayFollowUps ?? collect())->count() }}</span>
                    </div>
                    <a href="{{ route('calendar.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده تقویم</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if(($todayFollowUps ?? collect())->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">برای امروز پیگیری برنامه‌ریزی نشده است.</p>
                    @else
                        <ul class="divide-y divide-gray-100/70">
                            @foreach($todayFollowUps as $fu)
                                <li>
                                    <a href="{{ $fu['url'] ?? '#' }}" class="block px-4 py-2 hover:bg-white/70 focus:bg-white/70 transition outline-none">
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

                <div class="p-3 border-t border-gray-200/60 bg-white/70">
                    <a href="{{ route('calendar.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        رفتن به تقویم
                    </a>
                </div>
            </div>

            {{-- باکس پیگیری‌های معوق --}}
            <div class="w-full max-w-[400px] h-[400px] bg-gradient-to-br from-white via-white to-amber-50/40 border border-gray-200/70 shadow-sm rounded-xl flex flex-col overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200/60 flex items-center justify-between bg-white/70 backdrop-blur">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">پیگیری‌های روزهای قبل</h3>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100">{{ ($pastFollowUps ?? collect())->count() }}</span>
                    </div>
                    <a href="{{ route('calendar.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده تقویم</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if(($pastFollowUps ?? collect())->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">پیگیری معوقی وجود ندارد.</p>
                    @else
                        <ul class="divide-y divide-gray-100/70">
                            @foreach($pastFollowUps as $fu)
                                <li>
                                    <a href="{{ $fu['url'] ?? '#' }}" class="block px-4 py-2 hover:bg-white/70 focus:bg-white/70 transition outline-none">
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

                <div class="p-3 border-t border-gray-200/60 bg-white/70">
                    <a href="{{ route('calendar.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        رفتن به تقویم
                    </a>
                </div>
            </div>

            {{-- باکس آخرین اعلانات --}}
            <div class="w-full max-w-[400px] h-[400px] bg-gradient-to-br from-white via-white to-emerald-50/40 border border-gray-200/70 shadow-sm rounded-xl flex flex-col overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200/60 flex items-center justify-between bg-white/70 backdrop-blur">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">آخرین اعلانات</h3>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $notifications->count() }}</span>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده همه</a>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if($notifications->isEmpty())
                        <p class="px-4 py-3 text-xs text-gray-500">اعلانی وجود ندارد.</p>
                    @else
                        <ul class="divide-y divide-gray-100/70">
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
                                    <a href="{{ $itemUrl }}" class="block px-4 py-2 hover:bg-white/70 focus:bg-white/70 transition outline-none" aria-label="مشاهده اعلان">
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

                <div class="p-3 border-t border-gray-200/60 bg-white/70">
                    <a href="{{ route('notifications.index') }}" class="w-full inline-flex items-center justify-center text-[12px] font-medium px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition">
                        مشاهده همه اعلانات
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('leadChoiceModal');
    const openBtn = document.getElementById('openLeadModal');

    if (modal && openBtn) {
        const closeTargets = modal.querySelectorAll('[data-close-lead-modal]');
        const overlay = modal.querySelector('.lead-modal-overlay');

        const openModal = (event) => {
            event.preventDefault();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = (event) => {
            if (event) event.preventDefault();
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        openBtn.addEventListener('click', openModal);
        closeTargets.forEach((el) => el.addEventListener('click', closeModal));
        if (overlay) overlay.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    document.querySelectorAll('[data-report-card]').forEach((card) => {
        const toggle = card.querySelector('[data-report-toggle]');
        const panel = card.querySelector('[data-report-panel]');
        const icon = toggle ? toggle.querySelector('[data-report-icon]') : null;

        if (!toggle || !panel) return;

        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');

        toggle.addEventListener('click', () => {
            const isHidden = panel.classList.toggle('hidden');
            const expanded = !isHidden;
            toggle.setAttribute('aria-expanded', expanded.toString());

            if (icon) {
                icon.classList.toggle('rotate-180', expanded);
            }
        });
    });
});
</script>
@endsection
