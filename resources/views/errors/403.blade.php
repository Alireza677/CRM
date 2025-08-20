@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="text-center max-w-md bg-white rounded-[10px] shadow-lg p-6">
        <div class="text-xl text-gray-800 mb-6">
            شما امکان دسترسی به این بخش را ندارید
        </div>

        @if(session('error'))
          <div class="mb-6 p-3 rounded bg-red-100 text-red-800">
              {{ session('error') }}
          </div>
        @endif

        <div class="flex items-center justify-center gap-4">
            <a href="{{ url()->previous() }}" 
               class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 transition">
               بازگشت
            </a>
            <a href="{{ route('dashboard') }}" 
               class="px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 transition">
               داشبورد
            </a>
        </div>
    </div>
</div>
@endsection
