@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'تنظیمات'],
        ['title' => 'تنظیمات عمومی']
    ];
@endphp

<div class="max-w-3xl mx-auto mt-10">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800">تنظیمات عمومی</h1>
            <a href="{{ route('settings.index') }}" class="text-blue-600 hover:underline text-sm">بازگشت به تنظیمات</a>
        </div>

        <div class="space-y-6">
            <div>
                <label class="block mb-1 text-sm text-gray-700">نام سامانه</label>
                <input type="text" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="مثال: CRM شرکت شما" value="{{ config('app.name') }}" disabled>
                <p class="mt-1 text-xs text-gray-500">فعلاً فقط نمایش داده می‌شود. برای ویرایش می‌توانیم ذخیره‌سازی را اضافه کنیم.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm text-gray-700">زبان پیش‌فرض</label>
                    <select class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" disabled>
                        <option value="fa" selected>فارسی</option>
                        <option value="en">انگلیسی</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 text-sm text-gray-700">منطقه زمانی</label>
                    <input type="text" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" value="{{ config('app.timezone') }}" disabled>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm text-gray-700">واحد پول</label>
                    <select class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" disabled>
                        <option>ریال (IRR)</option>
                        <option>تومان (IRT)</option>
                        <option>دلار (USD)</option>
                        <option>یورو (EUR)</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 text-sm text-gray-700">فرمت تاریخ</label>
                    <select class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" disabled>
                        <option>Y-m-d</option>
                        <option>d/m/Y</option>
                        <option>m/d/Y</option>
                    </select>
                </div>
            </div>

            <div class="border-t pt-6">
                <p class="text-sm text-gray-600">این صفحه به صورت اولیه ساخته شد تا خطای نبود متد «general» رفع شود. اگر مایل باشید ذخیره‌سازی تنظیمات (در دیتابیس یا فایل تنظیمات) را هم اضافه می‌کنم.</p>
            </div>
        </div>
    </div>
    
</div>
@endsection

