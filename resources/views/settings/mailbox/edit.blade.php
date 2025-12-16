@extends('layouts.app')

@section('content')
@php
    $imapEncryption = old('imap_encryption', $mailbox->imap_encryption ?? 'none');
    $smtpEncryption = old('smtp_encryption', $mailbox->smtp_encryption ?? 'none');
    $isActive       = old('is_active', $mailbox->is_active ?? true);
@endphp

<div class="max-w-4xl mx-auto py-10" dir="rtl">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">تنظیمات صندوق ایمیل</h1>
            <p class="text-sm text-gray-500 mt-1">اتصال IMAP/SMTP از DirectAdmin را برای ایمیل خود ثبت کنید.</p>
        </div>
        <a href="{{ route('mail.index') }}" class="text-sm text-blue-600 hover:text-blue-800">بازگشت به ایمیل‌ها</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('settings.mailbox.update') }}" method="POST" class="bg-white shadow-sm rounded-lg border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">آدرس ایمیل</label>
                <input type="email" name="email_address" value="{{ old('email_address', $mailbox->email_address ?? '') }}"
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نام کاربری</label>
                <input type="text" name="username" value="{{ old('username', $mailbox->username ?? '') }}"
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">تنظیمات IMAP</h2>
                    <span class="text-xs text-gray-500">دریافت ایمیل</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">هاست IMAP</label>
                    <input type="text" name="imap_host" value="{{ old('imap_host', $mailbox->imap_host ?? '') }}"
                           class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">پورت IMAP</label>
                        <input type="number" name="imap_port" value="{{ old('imap_port', $mailbox->imap_port ?? '') }}"
                               class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رمزنگاری IMAP</label>
                        <select name="imap_encryption" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                            @foreach ($encryptionOptions as $value => $label)
                                <option value="{{ $value }}" @selected($imapEncryption === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">تنظیمات SMTP</h2>
                    <span class="text-xs text-gray-500">ارسال ایمیل</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">هاست SMTP</label>
                    <input type="text" name="smtp_host" value="{{ old('smtp_host', $mailbox->smtp_host ?? '') }}"
                           class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">پورت SMTP</label>
                        <input type="number" name="smtp_port" value="{{ old('smtp_port', $mailbox->smtp_port ?? '') }}"
                               class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رمزنگاری SMTP</label>
                        <select name="smtp_encryption" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                            @foreach ($encryptionOptions as $value => $label)
                                <option value="{{ $value }}" @selected($smtpEncryption === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور (اختیاری)</label>
                <input type="password" name="password" value=""
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="در صورت نیاز رمز جدید را وارد کنید">
                <p class="text-xs text-gray-500 mt-1">اگر این فیلد را خالی بگذارید، رمز فعلی بدون تغییر می‌ماند.</p>
            </div>
            <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                       class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                       {{ (bool) $isActive ? 'checked' : '' }}>
                <label for="is_active" class="text-sm text-gray-700">فعال‌سازی همگام‌سازی ایمیل</label>
            </div>
        </div>

        <div class="pt-2 flex justify-end">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                ذخیره تنظیمات
            </button>
        </div>
    </form>
</div>
@endsection
