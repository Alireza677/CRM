@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center bg-gray-50">
    <div class="max-w-xl w-full text-center p-6 bg-white rounded-[10px] shadow">
        <h1 class="text-3xl font-bold mb-3">اعتبار صفحه به پایان رسیده است (419)</h1>
        <p class="text-gray-700 mb-3">
            مدت‌زمان اعتبار صفحه یا نشست شما تمام شده است. این اتفاق معمولاً وقتی رخ می‌دهد که مدت زیادی صفحه باز بوده
            یا ارتباط شما با سامانه قطع شده باشد.
        </p>
        <p class="text-gray-600 text-sm mb-4">
            لطفاً صفحه را رفرش کنید، در صورت نیاز دوباره وارد سامانه شوید و مجدداً عملیات را انجام دهید.
        </p>

        <div class="text-right text-xs text-gray-600 bg-gray-50 border rounded-md p-3 mb-5 leading-relaxed">
            <p class="font-semibold mb-1">اگر مشکل تکرار شد، متن زیر را برای پشتیبانی ارسال کنید:</p>
            <p class="font-mono break-all">
                [CRM Error] code=419 | url={{ request()->fullUrl() }} | user={{ auth()->id() ?? 'guest' }}
            </p>
        </div>

        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">تلاش مجدد</a>
    </div>
</div>
@endsection
