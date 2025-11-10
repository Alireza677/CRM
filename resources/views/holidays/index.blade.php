@extends('layouts.app')

@section('title', 'مدیریت تعطیلات شرکت')

@section('content')
  <div class="max-w-4xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">مدیریت تعطیلات شرکت</h1>

    @if (session('status'))
      <div class="mb-4 rounded bg-green-50 text-green-700 px-3 py-2">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-md shadow p-4 mb-8">
      <h2 class="font-semibold mb-3">ثبت تعطیلی جدید</h2>
      <form action="{{ route('holidays.store') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">تاریخ</label>
            <input name="date" type="text" required placeholder="مثال: 1403/08/20 یا 2025-11-10"
                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   value="{{ old('date') }}">
            @error('date')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div>
            <label class="block text-sm mb-1">عنوان (اختیاری)</label>
            <input name="title" type="text" placeholder="پیش‌فرض: تعطیلی شرکت"
                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   value="{{ old('title') }}">
            @error('title')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="flex items-center gap-2 mt-6 md:mt-0">
            <input id="notify" name="notify" type="checkbox" value="1" class="rounded" {{ old('notify') ? 'checked' : '' }}>
            <label for="notify">ارسال اعلان (SMS) در آینده</label>
          </div>
        </div>
        <div class="pt-2">
          <button class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">ثبت</button>
          <span class="text-gray-500 text-sm mr-2">فعلاً پیامکی ارسال نمی‌شود؛ فقط ذخیره می‌شود.</span>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-md shadow">
      <div class="p-4 border-b font-semibold">لیست تعطیلات</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-right px-3 py-2">تاریخ</th>
              <th class="text-right px-3 py-2">عنوان</th>
              <th class="text-right px-3 py-2">ارسال اعلان</th>
              <th class="text-left px-3 py-2">اقدامات</th>
            </tr>
          </thead>
          <tbody>
          @forelse ($holidays as $h)
            <tr class="border-t">
              <td class="px-3 py-2">{{ optional($h->date)->format('Y-m-d') }}</td>
              <td class="px-3 py-2">{{ $h->title ?: 'تعطیلی شرکت' }}</td>
              <td class="px-3 py-2">{{ $h->notify ? 'بله' : 'خیر' }}</td>
              <td class="px-3 py-2 text-left">
                <a href="{{ route('holidays.edit', $h) }}" class="text-blue-600 hover:underline">ویرایش</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">موردی ثبت نشده است</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="p-3">{{ $holidays->links() }}</div>
    </div>
  </div>
@endsection

