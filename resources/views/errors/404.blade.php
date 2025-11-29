@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center bg-gray-50">
    <div class="max-w-xl w-full text-center p-6 bg-white rounded-[10px] shadow">
        <h1 class="text-3xl font-bold mb-3">صفحه موردنظر پیدا نشد (404)</h1>
        <p class="text-gray-700 mb-3">
            ممکن است آدرس را اشتباه وارد کرده باشید، لینک قدیمی باشد، یا صفحه حذف شده باشد.
        </p>
        <p class="text-gray-600 text-sm mb-4">
            اگر این خطا را هنگام کار عادی با سامانه مشاهده کرده‌اید (مثلاً از منوی اصلی وارد شده‌اید)،
            لطفاً آن را به تیم فنی اطلاع دهید.
        </p>

        <div class="text-right text-xs text-gray-600 bg-gray-50 border rounded-md p-3 mb-5 leading-relaxed">
            <p class="font-semibold mb-1">لطفاً متن زیر را کپی کرده و برای پشتیبانی ارسال کنید:</p>
            <p class="font-mono break-all">
                [CRM Error] code=404 | url={{ request()->fullUrl() }} | user={{ auth()->id() ?? 'guest' }}
            </p>
        </div>

        <div class="flex items-center justify-center gap-3">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded-md">بازگشت</a>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">صفحه اصلی</a>
        </div>
    </div>
</div>
@endsection
