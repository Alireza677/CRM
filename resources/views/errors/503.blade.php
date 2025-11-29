@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center bg-gray-50">
    <div class="max-w-xl w-full text-center p-6 bg-white rounded-[10px] shadow">
        <h1 class="text-3xl font-bold mb-3">سرویس موقتاً در دسترس نیست (503)</h1>
        <p class="text-gray-700 mb-3">
            سامانه در حال به‌روزرسانی یا انجام عملیات فنی است و در حال حاضر امکان پاسخ‌گویی وجود ندارد.
        </p>
        <p class="text-gray-600 text-sm mb-4">
            لطفاً چند دقیقهٔ دیگر دوباره تلاش کنید. در صورت تداوم خطا، موضوع را به تیم فنی اطلاع دهید.
        </p>

        <div class="text-right text-xs text-gray-600 bg-gray-50 border rounded-md p-3 mb-5 leading-relaxed">
            <p class="font-semibold mb-1">برای گزارش مشکل، متن زیر را برای پشتیبانی ارسال کنید:</p>
            <p class="font-mono break-all">
                [CRM Error] code=503 | url={{ request()->fullUrl() }} | user={{ auth()->id() ?? 'guest' }}
            </p>
        </div>

        <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">بازگشت به صفحه اصلی</a>
    </div>
</div>
@endsection
