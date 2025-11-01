@extends('layouts.app')

@section('title', 'تقویم')

@section('content')
  <div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-3">
      <a href="{{ route('activities.create') }}"
         class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
         + ایجاد وظیفه
      </a>

      <!-- فیلتر تقویم: شخصی / مشترک -->
      <div id="calendar-filter" class="flex gap-2" dir="rtl">
        <button type="button" data-scope="shared"
                class="px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">
          تقویم مشترک
        </button>
        <button type="button" data-scope="personal"
                class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
          تقویم شخصی
        </button>
      </div>
    </div>

    <h1 class="text-2xl font-semibold mb-4">تقویم ماهانه</h1>

    {{-- ظرف تقویم (آدرس فید رویدادها از اینجا به JS پاس می‌شود) --}}
    <div id="calendar"
         class="calendar-rtl bg-white rounded-md shadow p-3"
         data-default-scope="personal"
         data-events-url="{{ route('calendar.events') }}">
    </div>
  </div>
@endsection

@vite([
  'resources/css/calendar.css',   {{-- سفارشی خودت --}}
  'resources/js/calendar.js'      {{-- کد جاوااسکریپت تقویم --}}
])
