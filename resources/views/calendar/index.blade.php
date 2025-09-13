@extends('layouts.app')

@section('title', 'تقویم')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-6">
        <h1 class="text-2xl font-semibold mb-4">تقویم ماهانه</h1>

        {{-- ظرف تقویم --}}
        <div id="calendar" class="calendar-rtl bg-white rounded-md shadow p-3"></div>
    </div>
@endsection

@vite([
  
  'resources/css/calendar.css',   {{-- سفارشی --}}
  'resources/js/calendar.js'      {{-- فقط JS، بدون import CSS --}}
])

