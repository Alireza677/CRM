@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center bg-gray-50">
    <div class="max-w-xl w-full text-center p-6 bg-white rounded-[10px] shadow">
        <h1 class="text-3xl font-bold mb-3">درخواست‌های مکرر (429)</h1>
        <p class="text-gray-700 mb-3">
            در مدت‌زمان کوتاه تعداد زیادی درخواست به سامانه ارسال شده است،
            به همین دلیل موقتاً از ادامهٔ درخواست جلوگیری شده است.
        </p>
        <p class="text-gray-600 text-sm mb-4">
            لطفاً چند لحظه صبر کنید و سپس دوباره تلاش کنید. اگر این پیام را بدون کلیک زیاد یا رفرش پشت‌سرهم می‌بینید،
            آن را به تیم فنی اطلاع دهید.
        </p>

        <div class="text-right text-xs text-gray-600 bg-gray-50 border rounded-md p-3 mb-5 leading-relaxed">
            <p class="font-semibold mb-1">لطفاً متن زیر را کپی کرده و برای پشتیبانی ارسال کنید:</p>
            <p class="font-mono break-all">
                [CRM Error] code=429 | url={{ request()->fullUrl() }} | user={{ auth()->id() ?? 'guest' }}
            </p>
        </div>

        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">بازگشت</a>
    </div>
</div>
@endsection
