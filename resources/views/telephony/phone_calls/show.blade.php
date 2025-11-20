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

    $statusBadgeClasses = [
        'completed' => 'bg-emerald-50 text-emerald-700',
        'answered' => 'bg-green-100 text-green-800',
        'in-progress' => 'bg-blue-100 text-blue-800',
        'failed' => 'bg-red-100 text-red-700',
        'no-answer' => 'bg-yellow-50 text-yellow-700',
        'canceled' => 'bg-gray-200 text-gray-700',
    ];

    $directionBadgeClasses = [
        'inbound' => 'bg-indigo-50 text-indigo-700',
        'outbound' => 'bg-purple-50 text-purple-700',
    ];

    $organizationPayload = data_get($phoneCall->payload_raw ?? [], 'organization');
    if (is_array($organizationPayload)) {
        $organizationName = $organizationPayload['name'] ?? ($organizationPayload['title'] ?? null);
        $organizationPhone = $organizationPayload['phone'] ?? null;
        $organizationEmail = $organizationPayload['email'] ?? null;
        $organizationWebsite = $organizationPayload['website'] ?? null;
        $organizationDescription = $organizationPayload['description'] ?? null;
    } else {
        $organizationName = $organizationPayload;
        $organizationPhone = $organizationEmail = $organizationWebsite = $organizationDescription = null;
    }
@endphp

@section('content')
    <div class="bg-gray-100  py-4">
        <div class=" mx-auto">
            {{-- هدر موبایل --}}
            <div class="flex items-center justify-between px-4 py-4 md:hidden">
                <div>
                    <p class="text-xs text-gray-500 mb-1">تماس تلفنی</p>
                    <p class="text-lg font-semibold text-gray-800">
                        {{ $phoneCall->customer_name ?? $phoneCall->customer?->name ?? 'بدون نام' }}
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ $directionLabels[$phoneCall->direction] ?? $phoneCall->direction }}
                        @if($phoneCall->started_at)
                            • {{ $phoneCall->started_at->format('Y/m/d H:i') }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('telephony.phone-calls.index') }}"
                       class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700">
                        بازگشت
                    </a>
                    <button id="mobileMenuBtn"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white"
                            aria-label="باز کردن منو" aria-controls="mobileSidebar" aria-expanded="false">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex relative">
                <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>

                <aside id="mobileSidebar"
                       class="fixed right-0 top-[105px] h-[calc(100vh-125px)] w-72 bg-white shadow-lg z-40 border-l
                              transform translate-x-full transition-transform duration-200 ease-out
                              md:translate-x-0 md:w-64 md:overflow-y-auto md:static md:h-auto md:border-none md:shadow-none">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">مسیرها</p>
                                <h2 class="text-base font-semibold text-gray-800">جزئیات تماس</h2>
                            </div>
                            <button id="closeSidebarBtn"
                                    class="md:hidden inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100"
                                    aria-label="بستن منو">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <nav class="space-y-1" role="tablist">
                            <a href="#"
                               data-tab="summary"
                               class="phone-call-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                                <span class="flex items-center space-x-2 rtl:space-x-reverse">
                                    <i class="fas fa-th-large"></i>
                                    <span>خلاصه</span>
                                </span>
                            </a>

                            <a href="#"
                               data-tab="info"
                               class="phone-call-tab flex items-center justify-between px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                                <span class="flex items-center space-x-2 rtl:space-x-reverse">
                                    <i class="fas fa-info-circle"></i>
                                    <span>اطلاعات</span>
                                </span>
                            </a>

                            <a href="#"
                               data-tab="notes"
                               class="phone-call-tab flex items-center justify-between px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                                <span class="flex items-center space-x-2 rtl:space-x-reverse">
                                    <i class="fas fa-sticky-note"></i>
                                    <span>یادداشت‌ها</span>
                                </span>
                            </a>

                            <a href="#"
                               data-tab="relations"
                               class="phone-call-tab flex items-center justify-between px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                                <span class="flex items-center space-x-2 rtl:space-x-reverse">
                                    <i class="fas fa-users"></i>
                                    <span>مخاطب و سازمان</span>
                                </span>
                            </a>
                        </nav>
                    </div>
                </aside>

                <main class="flex-1 px-4 md:px-8 pb-12 mr-0 ">
                    <div class="hidden md:flex justify-between items-center mb-8 mt-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">تماس تلفنی</p>
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ $phoneCall->customer_name ?? $phoneCall->customer?->name ?? 'بدون نام' }}
                            </h1>
                            <p class="text-sm text-gray-500 mt-1">
                                شماره {{ $phoneCall->customer_number }}
                                @if($phoneCall->started_at)
                                    • {{ $phoneCall->started_at->format('Y/m/d H:i') }}
                                @endif
                            </p>
                        </div>

                        <a href="{{ route('telephony.phone-calls.index') }}"
                           class="inline-flex items-center px-4 py-2 text-sm text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50">
                            بازگشت به لیست
                        </a>
                    </div>

                    <div id="phone-call-tab-content" class="space-y-8">
                        <section data-tab-panel="summary" class="phone-call-tab-panel space-y-6">
                            <div class="bg-white rounded-2xl shadow-sm p-6">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">تماس ثبت‌شده</p>
                                        <p class="text-2xl font-semibold text-gray-900">
                                            {{ $phoneCall->customer_name ?? $phoneCall->customer?->name ?? 'بدون نام' }}
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ $directionLabels[$phoneCall->direction] ?? $phoneCall->direction }}
                                            @if($phoneCall->started_at)
                                                • {{ $phoneCall->started_at->format('Y/m/d H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full {{ $statusBadgeClasses[$phoneCall->status] ?? 'bg-gray-200 text-gray-700' }}">
                                            {{ $statusLabels[$phoneCall->status] ?? $phoneCall->status }}
                                        </span>
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full {{ $directionBadgeClasses[$phoneCall->direction] ?? 'bg-gray-200 text-gray-700' }}">
                                            {{ $directionLabels[$phoneCall->direction] ?? $phoneCall->direction }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <p class="text-xs text-gray-500 mb-1">شماره مشتری</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ $phoneCall->customer_number }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <p class="text-xs text-gray-500 mb-1">اداره شده توسط</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ $phoneCall->handledBy?->name ?? '—' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <p class="text-xs text-gray-500 mb-1">شناسه منبع</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ $phoneCall->source_identifier ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section data-tab-panel="info" class="phone-call-tab-panel hidden space-y-6">
                            <div class="bg-white rounded-2xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">مشخصات تماس</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                                    <div>
                                        <p class="text-gray-500 mb-1">وضعیت تماس</p>
                                        <p class="font-semibold text-gray-900">{{ $statusLabels[$phoneCall->status] ?? $phoneCall->status }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">جهت تماس</p>
                                        <p class="font-semibold text-gray-900">{{ $directionLabels[$phoneCall->direction] ?? $phoneCall->direction }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">زمان شروع</p>
                                        <p class="font-semibold text-gray-900">
                                            {{ optional($phoneCall->started_at)->format('Y-m-d H:i') ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">شناسه مشتری (سیستمی)</p>
                                        <p class="font-semibold text-gray-900">{{ $phoneCall->customer_id ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">مشتری</p>
                                        <p class="font-semibold text-gray-900">
                                            {{ $phoneCall->customer_name ?? $phoneCall->customer?->name ?? 'بدون نام' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">کاربر مسئول</p>
                                        <p class="font-semibold text-gray-900">{{ $phoneCall->handledBy?->name ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-3">Payload خام</h2>
                                @if($phoneCall->payload_raw)
                                    <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-xs overflow-auto">{{ json_encode($phoneCall->payload_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <p class="text-gray-500 text-sm">داده‌ای ثبت نشده است.</p>
                                @endif
                            </div>
                        </section>

                        <section data-tab-panel="notes" class="phone-call-tab-panel hidden space-y-6">
                            <div class="bg-white rounded-2xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold text-gray-800">یادداشت‌های تماس</h2>
                                    <span class="text-xs text-gray-500">
                                        آخرین بروزرسانی {{ $phoneCall->updated_at?->diffForHumans() ?? 'نامشخص' }}
                                    </span>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4 text-gray-700 leading-7 min-h-[120px]">
                                    {!! nl2br(e($phoneCall->notes ?? 'یادداشتی ثبت نشده است.')) !!}
                                </div>
                            </div>
                        </section>

                        <section data-tab-panel="relations" class="phone-call-tab-panel hidden space-y-6">
                            <div class="bg-white rounded-2xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">مخاطب مرتبط</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                                    <div>
                                        <p class="text-gray-500 mb-1">نام مخاطب</p>
                                        <p class="font-semibold text-gray-900">
                                            {{ $phoneCall->customer_name ?? $phoneCall->customer?->name ?? 'بدون نام' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">شماره تماس</p>
                                        <p class="font-semibold text-gray-900">{{ $phoneCall->customer_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">ایمیل</p>
                                        <p class="font-semibold text-gray-900">
                                            {{ $phoneCall->customer?->email ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">آدرس</p>
                                        <p class="font-semibold text-gray-900">
                                            {{ $phoneCall->customer?->address ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">سازمان</h2>
                                @if($organizationName)
                                    <div class="space-y-3 text-sm">
                                        <div>
                                            <p class="text-gray-500 mb-1">نام سازمان</p>
                                            <p class="font-semibold text-gray-900">{{ $organizationName }}</p>
                                        </div>
                                        @if($organizationPhone)
                                            <div>
                                                <p class="text-gray-500 mb-1">تلفن</p>
                                                <p class="font-semibold text-gray-900">{{ $organizationPhone }}</p>
                                            </div>
                                        @endif
                                        @if($organizationEmail)
                                            <div>
                                                <p class="text-gray-500 mb-1">ایمیل</p>
                                                <p class="font-semibold text-gray-900">{{ $organizationEmail }}</p>
                                            </div>
                                        @endif
                                        @if($organizationWebsite)
                                            <div>
                                                <p class="text-gray-500 mb-1">وب‌سایت</p>
                                                <a href="{{ $organizationWebsite }}" target="_blank" rel="noopener"
                                                   class="font-semibold text-blue-600 hover:text-blue-800">
                                                    {{ $organizationWebsite }}
                                                </a>
                                            </div>
                                        @endif
                                        @if($organizationDescription)
                                            <div>
                                                <p class="text-gray-500 mb-1">توضیحات</p>
                                                <p class="text-gray-700">{{ $organizationDescription }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">
                                        این تماس به سازمانی متصل نشده است. در صورت نیاز می‌توانید اطلاعات سازمان را در Payload ذخیره کنید
                                        تا در این بخش نمایش داده شود.
                                    </p>
                                @endif
                            </div>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.phone-call-tab');
            const panels = document.querySelectorAll('.phone-call-tab-panel');
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileOverlay');
            const openBtn = document.getElementById('mobileMenuBtn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            const activeClasses = ['bg-blue-100', 'text-blue-800', 'font-semibold'];

            function setActiveTab(tabName) {
                tabs.forEach((tab) => {
                    if (tab.dataset.tab === tabName) {
                        tab.classList.add(...activeClasses);
                        tab.setAttribute('aria-current', 'true');
                    } else {
                        tab.classList.remove(...activeClasses);
                        tab.removeAttribute('aria-current');
                    }
                });

                panels.forEach((panel) => {
                    if (panel.dataset.tabPanel === tabName) {
                        panel.classList.remove('hidden');
                    } else {
                        panel.classList.add('hidden');
                    }
                });
            }

            function closeSidebar() {
                if (!sidebar) return;
                sidebar.classList.add('translate-x-full');
                overlay?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                openBtn?.setAttribute('aria-expanded', 'false');
            }

            function openSidebar() {
                if (!sidebar) return;
                sidebar.classList.remove('translate-x-full');
                overlay?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                openBtn?.setAttribute('aria-expanded', 'true');
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', (event) => {
                    event.preventDefault();
                    setActiveTab(tab.dataset.tab);
                    if (window.matchMedia('(max-width: 767px)').matches) {
                        closeSidebar();
                    }
                });
            });

            openBtn?.addEventListener('click', (event) => {
                event.preventDefault();
                const expanded = openBtn.getAttribute('aria-expanded') === 'true';
                expanded ? closeSidebar() : openSidebar();
            });

            closeBtn?.addEventListener('click', (event) => {
                event.preventDefault();
                closeSidebar();
            });

            overlay?.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && window.matchMedia('(max-width: 767px)').matches) {
                    closeSidebar();
                }
            });

            setActiveTab('summary');
        });
    </script>
@endpush
