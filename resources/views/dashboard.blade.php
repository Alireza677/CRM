@extends('layouts.app')

@section('content')
<div class="container py-12" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <h2 class="text-2xl font-semibold text-gray-800 mb-6">داشبورد</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- باکس اول: ایجاد فرصت فروش --}}
            <div class="bg-white shadow rounded-lg p-6 flex flex-col items-center text-center hover:shadow-md transition">
                <div class="text-blue-600 text-3xl mb-2">📈</div>
                <h3 class="text-lg font-bold mb-2">ایجاد فرصت فروش</h3>
                <p class="text-sm text-gray-600 mb-4">فرصت‌های فروش جدیدی را ثبت و پیگیری کنید.</p>
                <a href="{{ route('sales.opportunities.create') }}" class="text-blue-500 hover:text-blue-700 font-semibold">
                    + ثبت فرصت فروش
                </a>
            </div>

            {{-- باکس دوم: ایجاد سرنخ --}}
            <div class="bg-white shadow rounded-lg p-6 flex flex-col items-center text-center hover:shadow-md transition">
                <div class="text-green-600 text-3xl mb-2">🧩</div>
                <h3 class="text-lg font-bold mb-2">ایجاد سرنخ</h3>
                <p class="text-sm text-gray-600 mb-4">مشتریان بالقوه را به سرنخ فروش تبدیل کنید.</p>
                <a href="{{ route('marketing.leads.create') }}" class="text-green-500 hover:text-green-700 font-semibold">
                    + افزودن سرنخ جدید
                </a>
            </div>

            {{-- باکس سوم: اعلانات --}}
            <div class="bg-white shadow rounded-lg p-6 flex flex-col items-center text-center hover:shadow-md transition">
                <div class="text-yellow-600 text-3xl mb-2">🔔</div>
                <h3 class="text-lg font-bold mb-2">اعلانات</h3>
                <p class="text-sm text-gray-600 mb-4">آخرین رویدادها و اعلان‌های مربوط به شما.</p>
                <a href="{{ route('notifications.index') }}" class="text-yellow-500 hover:text-yellow-700 font-semibold">
                    مشاهده اعلانات
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
