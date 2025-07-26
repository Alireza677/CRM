@extends('layouts.app')

@section('content')
<div class="container py-12" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-8">داشبورد</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- کارت ۲: ایجاد سرنخ --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-green-500 text-4xl mb-3">🧩</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2"> سرنخ</h3>
                <p class="text-sm text-gray-700 mb-4">مشتریان بالقوه را به سرنخ فروش تبدیل کنید.</p>
                <a href="{{ route('marketing.leads.index') }}" class="text-green-600 hover:text-green-800 font-semibold transition">
                    +  مشاهده  سرنخ ها
                </a>
            </div>
            {{-- کارت ۱: ایجاد فرصت فروش --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-blue-500 text-4xl mb-3">📈</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2"> فرصت فروش</h3>
                <p class="text-sm text-gray-700 mb-4">فرصت‌های فروش جدیدی را ثبت و پیگیری کنید.</p>
                <a href="{{ route('sales.opportunities.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold transition">
                    + مشاهده فرصت های فروش
                </a>
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

            {{-- کارت ۵: پیش فاکتور --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-red-500 text-4xl mb-3">🧾</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">پیش‌فاکتورها</h3>
                <p class="text-sm text-gray-700 mb-4">ایجاد یا بررسی پیش‌فاکتورهای صادرشده.</p>
                <a href="{{ route('sales.proformas.index') }}" class="text-red-600 hover:text-red-800 font-semibold transition">
                    مشاهده پیش‌فاکتورها
                </a>
            </div>
            
            {{-- کارت ۴: مخاطبین --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-purple-500 text-4xl mb-3">👤</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">مخاطبین</h3>
                <p class="text-sm text-gray-700 mb-4">مشاهده و مدیریت لیست مخاطبین ثبت‌شده.</p>
                <a href="{{ route('sales.contacts.index') }}" class="text-purple-600 hover:text-purple-800 font-semibold transition">
                    مشاهده مخاطبین
                </a>
            </div>

            

            {{-- کارت ۶: سازمان‌ها --}}
            <div class="backdrop-blur-md bg-white/30 border border-white/20 shadow-md rounded-2xl p-6 flex flex-col items-center text-center transition hover:shadow-[0_12px_24px_-4px_rgba(0,0,0,0.2)]">
                <div class="text-indigo-500 text-4xl mb-3">🏢</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">سازمان‌ها</h3>
                <p class="text-sm text-gray-700 mb-4">مدیریت شرکت‌ها و سازمان‌های ثبت‌شده.</p>
                <a href="{{ route('sales.organizations.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                    مشاهده سازمان‌ها
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
