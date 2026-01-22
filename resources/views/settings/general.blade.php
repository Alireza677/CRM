@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'تنظیمات'],
        ['title' => 'تنظیمات عمومی'],
    ];
@endphp

<div class="max-w-3xl mx-auto mt-10">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800">تنظیمات عمومی</h1>
            <a href="{{ route('settings.index') }}" class="text-blue-600 hover:underline text-sm">
                بازگشت به تنظیمات
            </a>
        </div>

        <div class="space-y-6">
            {{-- نام سامانه --}}
            <div>
                <label class="block mb-1 text-sm text-gray-700">نام سامانه</label>
                <input
                    type="text"
                    class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="مثال: CRM شرکت شما"
                    value="{{ config('app.name') }}"
                    disabled
                >
                <p class="mt-1 text-xs text-gray-500">
                    نام سامانه از فایل تنظیمات خوانده می‌شود و فعلاً از همین صفحه قابل ویرایش نیست.
                </p>
            </div>

            {{-- زبان و منطقه زمانی --}}
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
                    <input
                        type="text"
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        value="{{ config('app.timezone') }}"
                        disabled
                    >
                </div>
            </div>

            {{-- واحد پول و فرمت تاریخ --}}
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

            {{-- توضیح --}}
            <div class="border-t pt-6">
                <p class="text-sm text-gray-600">
                    این صفحه فعلاً به‌صورت اولیه ساخته شده تا خطای نبود متد «general» رفع شود.
                    اگر مایل باشید، ذخیره‌سازی تنظیمات (در دیتابیس یا فایل تنظیمات) را هم اضافه می‌کنم.
                </p>
            </div>

            {{-- حالت اضطراری منابع (Assets Emergency) --}}
            <div class="border-t pt-6">
                <form method="POST" action="{{ route('settings.general.assets-emergency') }}" class="flex items-center justify-between gap-4">
                    @csrf
                    <input type="hidden" name="assets_emergency" value="0">

                    <div>
                        <label class="block text-sm font-semibold text-gray-800">
                            حالت اضطراری منابع (بدون استفاده از CDN)
                        </label>
                        <p class="mt-1 text-xs text-gray-500">
                            اگر به هر دلیل دسترسی به CDN یا منابع خارجی دچار مشکل شد، با فعال کردن این گزینه
                            سامانه تلاش می‌کند منابع را به‌صورت داخلی/محلی بارگذاری کند.
                        </p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="assets_emergency"
                            value="1"
                            class="sr-only peer"
                            @checked($assetsEmergency)
                            onchange="this.form.submit()"
                        >
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-red-500 transition"></div>
                        <div class="absolute right-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition peer-checked:translate-x-[-20px]"></div>
                    </label>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
