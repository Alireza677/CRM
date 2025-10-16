@extends('layouts.app')

@section('content')
    <div class="py-8 px-4 sm:px-6 lg:px-8" x-data="{ tab: 'profile' }" dir="rtl">

        {{-- اطلاعات ورود و نقش --}}
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">پروفایل کاربری</h2>
            <h3 class="text-lg font-semibold mb-4 text-gray-700">اطلاعات ورود و نقش</h3>

            @php
                $u = Auth::user();
                $roles = method_exists($u, 'roles') ? $u->roles->pluck('name')->join('، ') : '—';
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-800 text-sm">
                <div><span class="font-semibold">ایمیل (نام کاربری):</span> {{ $u->email }}</div>
                <div><span class="font-semibold">نام:</span> {{ $u->name ?? '—' }}</div>
                {{-- اگر واقعاً ستون first_name / last_name داری، این دو خط را آزاد کن
                <div><span class="font-semibold">نام:</span> {{ $u->first_name ?? '—' }}</div>
                <div><span class="font-semibold">نام خانوادگی:</span> {{ $u->last_name ?? '—' }}</div>
                --}}
                <div><span class="font-semibold">نقش(ها):</span> {{ $roles ?: '—' }}</div>
                <div><span class="font-semibold">شماره موبایل:</span> {{ $u->mobile ?? '—' }}</div>
                <div><span class="font-semibold">تأیید موبایل:</span> {{ $u->mobile_verified_at ? \Carbon\Carbon::parse($u->mobile_verified_at)->format('Y/m/d H:i') : '—' }}</div>
                <div><span class="font-semibold">سطح دسترسی ادمین:</span> {{ $u->is_admin ? 'بله' : 'خیر' }}</div>

                {{-- اگر بعدها ستون‌ها را اضافه کردی این‌ها را آزاد کن
                <div><span class="font-semibold">وضعیت:</span> {{ $u->status ?? '—' }}</div>
                <div><span class="font-semibold">زبان:</span> {{ $u->language ?? '—' }}</div>
                <div><span class="font-semibold">تاریخ آخرین ورود:</span> 
                    {{ $u->last_login_at ? \Carbon\Carbon::parse($u->last_login_at)->format('Y/m/d H:i') : '—' }}
                </div>
                --}}
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
        </div>
    </div>
@endsection
