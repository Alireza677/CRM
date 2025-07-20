@extends('layouts.app')

@section('content')
    <div class="py-8 px-4 sm:px-6 lg:px-8" x-data="{ tab: 'profile' }" dir="rtl">

        {{-- اطلاعات ورود و نقش --}}
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">پروفایل کاربری</h2>
            <h3 class="text-lg font-semibold mb-4 text-gray-700">اطلاعات ورود و نقش</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-800 text-sm">
                <div><span class="font-semibold">ایمیل (نام کاربری):</span> {{ Auth::user()->email }}</div>
                <div><span class="font-semibold">نام:</span> {{ Auth::user()->first_name ?? '—' }}</div>
                <div><span class="font-semibold">نام خانوادگی:</span> {{ Auth::user()->last_name ?? '—' }}</div>
                <div><span class="font-semibold">نقش:</span> {{ Auth::user()->role->name ?? '—' }}</div>
                <div><span class="font-semibold">وضعیت:</span> {{ Auth::user()->status ?? '—' }}</div>
                <div><span class="font-semibold">زبان:</span> {{ Auth::user()->language ?? '—' }}</div>
                <div><span class="font-semibold">سطح دسترسی ادمین:</span> {{ Auth::user()->is_admin ? 'بله' : 'خیر' }}</div>
                <div><span class="font-semibold">شماره موبایل:</span> {{ Auth::user()->mobile ?? '—' }}</div>
                <div><span class="font-semibold">تاریخ آخرین ورود:</span> 
                    {{ Auth::user()->last_login_at ? \Carbon\Carbon::parse(Auth::user()->last_login_at)->format('Y/m/d H:i') : '—' }}
                </div>
            </div>
        </div>

        {{-- دکمه‌های تب --}}
        <div class="flex justify-center gap-4 mb-4">
            <button @click="tab = 'profile'"
                    class="px-4 py-2 rounded transition"
                    :class="tab === 'profile' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'">
                ویرایش اطلاعات
            </button>
            <button @click="tab = 'password'"
                    class="px-4 py-2 rounded transition"
                    :class="tab === 'password' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'">
                تغییر رمز عبور
            </button>
            <!-- <button @click="tab = 'delete'"
                    class="px-4 py-2 rounded transition"
                    :class="tab === 'delete' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'">
                حذف حساب
            </button> -->
        </div>

        {{-- محتوای تب‌ها --}}
        <div class="bg-white shadow rounded-lg p-6">

            {{-- تب: ویرایش اطلاعات --}}
            <div x-show="tab === 'profile'" x-cloak>
                <h3 class="text-lg font-semibold mb-4 text-gray-700">ویرایش اطلاعات</h3>
                @include('profile.partials.update-profile-information-form')
            </div>

            {{-- تب: تغییر رمز عبور --}}
            <div x-show="tab === 'password'" x-cloak>
                <h3 class="text-lg font-semibold mb-4 text-gray-700">تغییر رمز عبور</h3>
                @include('profile.partials.update-password-form')
            </div>

            <!-- {{-- تب: حذف حساب --}}
            <div x-show="tab === 'delete'" x-cloak>
                <h3 class="text-lg font-semibold mb-4 text-red-600">حذف حساب کاربری</h3>
                @include('profile.partials.delete-user-form')
            </div> -->

        </div>
    </div>
@endsection
