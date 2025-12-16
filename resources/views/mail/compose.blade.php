@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8" dir="rtl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-gray-800">ایجاد ایمیل جدید</h1>
            <a href="{{ route('mail.index') }}" class="text-sm text-blue-600 hover:text-blue-800">بازگشت</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('mail_trace_id') && $errors->has('general'))
            <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 text-sm">
                ارسال ناموفق بود. کد پیگیری: <span class="font-mono font-semibold">{{ session('mail_trace_id') }}</span>
            </div>
        @endif

        <form action="{{ route('mail.send') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="in_reply_to" value="{{ old('in_reply_to', request('in_reply_to')) }}">
            <input type="hidden" name="references" value="{{ old('references', request('references')) }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">به (جداکننده: ,)</label>
                <input type="text" name="to" value="{{ old('to', $defaults['to'] ?? '') }}" required
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cc (اختیاری)</label>
                <input type="text" name="cc" value="{{ old('cc', $defaults['cc'] ?? '') }}"
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">عنوان</label>
                <input type="text" name="subject" value="{{ old('subject', $defaults['subject'] ?? '') }}"
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">متن پیام</label>
                <textarea name="body" rows="8"
                          class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('body', $defaults['body'] ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">پیوست‌ها</label>
                <input type="file" name="attachments[]" multiple
                       class="block w-full text-sm text-gray-700 border border-gray-300 rounded cursor-pointer focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">حداکثر 10 مگابایت برای هر فایل.</p>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('mail.index') }}" class="px-4 py-2 rounded border border-gray-200 text-gray-700 hover:bg-gray-50">انصراف</a>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">ارسال</button>
            </div>
        </form>
    </div>
</div>
@endsection
