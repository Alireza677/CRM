@extends('layouts.app')

@section('content')
<div dir="rtl" class="min-h-[60vh] flex items-center justify-center bg-gray-50">
    <div class="max-w-xl w-full text-center p-6 bg-white rounded-[10px] shadow">
        <h1 class="text-3xl font-bold mb-3">عدم دسترسی (403)</h1>
        <p class="text-gray-700 mb-3">
            شما مجوز دسترسی به این بخش از سامانه را ندارید.
            این خطا معمولاً زمانی رخ می‌دهد که نقش کاربری شما اجازه مشاهده یا ویرایش این صفحه را نداشته باشد.
        </p>

        @if(session('error'))
            <p class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                {{ session('error') }}
            </p>
        @endif

        

        <div class="flex items-center justify-center gap-3">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded-md">بازگشت</a>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md">داشبورد</a>
        </div>
    </div>
</div>
@endsection
