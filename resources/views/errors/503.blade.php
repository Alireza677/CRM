@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center">
  <div class="max-w-xl w-full text-center p-6">
    <h1 class="text-3xl font-bold mb-3">سرویس موقتاً در دسترس نیست (503)</h1>
    <p class="text-gray-700 mb-6">در حال انجام به‌روزرسانی هستیم. لطفاً دقایقی بعد مراجعه کنید.</p>
    <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">بازگشت به صفحه اصلی</a>
  </div>
</div>
@endsection
