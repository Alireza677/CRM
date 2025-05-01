@props(['lead', 'activeSection' => 'summary'])

<div class="fixed right-0 top-0 h-full w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out z-40" dir="rtl">
    <!-- Sidebar Header -->
    <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 truncate">
            {{ $lead->first_name }} {{ $lead->last_name }}
        </h2>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="p-4 space-y-2">
        <a href="#summary" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'summary' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span>خلاصه</span>
        </a>

        <a href="#information" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'information' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>اطلاعات</span>
        </a>

        <a href="#updates" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'updates' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>بروزرسانی‌ها</span>
        </a>

        <a href="#notes" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'notes' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>یادداشت‌ها</span>
        </a>

        <a href="#activities" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'activities' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>فعالیت‌ها</span>
        </a>

        <a href="#documents" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'documents' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>اسناد</span>
        </a>

        <a href="#products" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'products' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span>محصولات</span>
        </a>

        <a href="#campaigns" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'campaigns' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
            <span>کمپین‌های تبلیغاتی</span>
        </a>

        <a href="#services" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'services' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
            </svg>
            <span>سرویس‌ها</span>
        </a>

        <a href="#approvals" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'approvals' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>تاییدیه‌ها</span>
        </a>

        <a href="#emails" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'emails' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>ایمیل‌ها</span>
        </a>

        <a href="#calls" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg {{ $activeSection === 'calls' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            <span>تماس‌های تلفنی</span>
        </a>
    </nav>
</div> 