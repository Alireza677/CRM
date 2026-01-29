@extends('layouts.app')

@section('title', 'ایجاد وظیفه')

@section('content')
<div class="max-w-4xl mx-auto p-4" dir="rtl">
  <h1 class="text-xl font-semibold mb-4">ایجاد فعالیت</h1>

  <form method="POST" action="{{ route('activities.store') }}" class="space-y-4">
    @csrf

    @include('activities._form', ['activity' => null, 'users' => $users, 'prefillRelated' => $prefillRelated ?? []])

    {{-- دکمه‌ها --}}
    <div class="pt-2 flex gap-2">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-md px-4 py-2">ثبت</button>
      <button type="button" onclick="window.history.back()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md px-4 py-2">انصراف</button>
    </div>
  </form>
</div>

{{-- پارشیال مودال‌ها --}}
@include('activities.modals')
@endsection

@vite(['resources/js/create.js'])
