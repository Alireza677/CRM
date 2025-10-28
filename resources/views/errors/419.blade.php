@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center">
  <div class="max-w-xl w-full text-center p-6">
    <h1 class="text-3xl font-bold mb-3">اعتبار صفحه به پایان رسیده (419)</h1>
    <p class="text-gray-700 mb-6">برای ادامه، صفحه را رفرش کنید و دوباره تلاش کنید.</p>
    <a href="{{ url()->previous() }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">تلاش مجدد</a>
  </div>
</div>
@endsection
