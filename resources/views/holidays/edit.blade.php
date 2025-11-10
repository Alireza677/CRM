@extends('layouts.app')

@section('title', 'ویرایش تعطیلی')

@section('content')
  <div class="max-w-3xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">ویرایش تعطیلی</h1>

    <div class="bg-white rounded-md shadow p-4">
      <form action="{{ route('holidays.update', $holiday) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">تاریخ</label>
            <input name="date" type="text" required placeholder="مثال: 1403/08/20 یا 2025-11-10"
                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   value="{{ old('date', optional($holiday->date)->format('Y-m-d')) }}">
            @error('date')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div>
            <label class="block text-sm mb-1">عنوان (اختیاری)</label>
            <input name="title" type="text" placeholder="پیش‌فرض: تعطیلی شرکت"
                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   value="{{ old('title', $holiday->title) }}">
            @error('title')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="flex items-center gap-2 mt-6 md:mt-0">
            <input id="notify" name="notify" type="checkbox" value="1" class="rounded" {{ old('notify', $holiday->notify) ? 'checked' : '' }}>
            <label for="notify">ارسال اعلان (SMS) در آینده</label>
          </div>
        </div>
        <div class="pt-2">
          <button class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">ذخیره</button>
          <a href="{{ route('holidays.index') }}" class="px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 mr-2">بازگشت</a>
        </div>
      </form>
    </div>
  </div>
@endsection

