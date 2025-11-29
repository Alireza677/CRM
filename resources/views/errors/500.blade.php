@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center bg-gray-50">
    <div class="max-w-xl w-full text-center p-6 bg-white rounded-[10px] shadow">
        <h1 class="text-3xl font-bold mb-3">خطای داخلی سرور (500)</h1>
        <p class="text-gray-700 mb-3">
            متأسفیم! مشکلی غیرمنتظره در سامانه رخ داده است. این خطا معمولاً ناشی از یک اشکال داخلی در نرم‌افزار
            یا ارتباط با پایگاه داده است.
        </p>
        <p class="text-gray-600 text-sm mb-4">
            اگر این خطا هنگام انجام یک کار مشخص (مثلاً ثبت فرم، ویرایش رکورد و...) رخ داد،
            لطفاً همان کار را در توضیحات خود برای تیم فنی بنویسید.
        </p>

        <div class="text-right text-xs text-gray-600 bg-gray-50 border rounded-md p-3 mb-5 leading-relaxed">
            <p class="font-semibold mb-1">لطفاً متن زیر را کپی کرده و برای پشتیبانی ارسال کنید:</p>
            @isset($exceptionId)
                <p class="font-mono break-all">
                    [CRM Error] code=500 | ref={{ $exceptionId }} | url={{ request()->fullUrl() }} | user={{ auth()->id() ?? 'guest' }}
                </p>
            @else
                <p class="font-mono break-all">
                    [CRM Error] code=500 | url={{ request()->fullUrl() }} | user={{ auth()->id() ?? 'guest' }}
                </p>
            @endisset
        </div>

        <div class="flex items-center justify-center gap-3">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded-md">بازگشت</a>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">صفحه اصلی</a>
        </div>
    </div>
</div>
@endsection
