@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center">
  <div class="max-w-xl w-full text-center p-6">
    <h1 class="text-3xl font-bold mb-3">خطای داخلی سرور (500)</h1>
    <p class="text-gray-700 mb-4">
      متأسفیم! مشکلی در سامانه رخ داده است و در حال بررسی آن هستیم.
    </p>
    @isset($exceptionId)
      <p class="text-gray-600 mb-6">
        کد پیگیری: <span class="font-mono">{{ $exceptionId }}</span>
      </p>
    @endisset
    <div class="flex items-center justify-center gap-3">
      <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded-md">بازگشت</a>
      <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">صفحه اصلی</a>
    </div>
  </div>
</div>
@endsection
