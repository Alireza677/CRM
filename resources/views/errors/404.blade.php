@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center">
  <div class="max-w-xl w-full text-center p-6">
    <h1 class="text-3xl font-bold mb-3">صفحه موردنظر پیدا نشد (404)</h1>
    <p class="text-gray-700 mb-6">ممکن است آدرس را اشتباه وارد کرده باشید یا صفحه حذف شده باشد.</p>
    <div class="flex items-center justify-center gap-3">
      <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded-md">بازگشت</a>
      <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">صفحه اصلی</a>
    </div>
  </div>
</div>
@endsection
