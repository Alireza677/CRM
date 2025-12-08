@extends('layouts.app')

@section('title', 'ویرایش وظیفه')

@section('content')
<div class="max-w-4xl mx-auto p-4" dir="rtl">
  <h1 class="text-xl font-semibold mb-4">ویرایش وظیفه</h1>

  <form method="POST" action="{{ route('activities.update', $activity->id) }}" class="space-y-4">
    @csrf
    @method('PUT')

    @include('activities._form', ['activity' => $activity, 'users' => $users, 'prefillRelated' => []])

    {{-- دکمه‌ها --}}
    <div class="pt-2 flex gap-2">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-md px-4 py-2">به‌روزرسانی</button>
      <a href="{{ route('activities.show', $activity->id) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md px-4 py-2">انصراف</a>
    </div>
  </form>
</div>

{{-- پارشیال مودال‌ها --}}
@include('activities.modals') 
@endsection

@vite(['resources/js/create.js'])
