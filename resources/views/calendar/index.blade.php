@extends('layouts.app')

@section('title', 'تقویم')

@section('content')
  <div class="max-w-7xl mx-auto px-4 py-6">
    <a href="{{ route('activities.create') }}"
       class="inline-flex items-center px-3 py-2 mb-3 rounded-md bg-blue-600 text-white hover:bg-blue-700">
       + ایجاد وظیفه
    </a>

    <h1 class="text-2xl font-semibold mb-4">تقویم ماهانه</h1>

    {{-- ظرف تقویم (آدرس فید رویدادها از اینجا به JS پاس می‌شود) --}}
    <div id="calendar"
         class="calendar-rtl bg-white rounded-md shadow p-3"
         data-events-url="{{ route('calendar.events') }}">
    </div>
  </div>
@endsection

@vite([
  'resources/css/calendar.css',   {{-- سفارشی خودت --}}
  'resources/js/calendar.js'      {{-- کد جاوااسکریپت تقویم --}}
])
