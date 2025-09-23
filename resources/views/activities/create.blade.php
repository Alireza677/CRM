@extends('layouts.app')

@section('title', 'ایجاد وظیفه')

@section('content')
<div class="max-w-4xl mx-auto p-4" dir="rtl">
  <h1 class="text-xl font-semibold mb-4">ایجاد وظیفه</h1>

  <form method="POST" action="{{ route('activities.store') }}" class="space-y-4">
    @csrf

    {{-- موضوع --}}
    <div>
      <label class="block text-sm mb-1">موضوع</label>
      <input name="subject" class="w-full rounded-md border p-2" required>
    </div>

    {{-- تاریخ‌ها (با انتخاب ساعت) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">تاریخ شروع</label>
        <input type="hidden" id="start_at" name="start_at" value="{{ old('start_at') }}">
        <input
          type="text"
          id="start_at_display"
          class="persian-datepicker w-full rounded-md border p-2"
          data-alt-field="start_at"
          data-prefill="{{ old('start_at') ? '1' : '0' }}"
          autocomplete="off"
          value="">
        @error('start_at')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">موعد/پایان</label>
        <input type="hidden" id="due_at" name="due_at" value="{{ old('due_at') }}">
        <input
          type="text"
          id="due_at_display"
          class="persian-datepicker w-full rounded-md border p-2"
          data-alt-field="due_at"
          data-prefill="{{ old('due_at') ? '1' : '0' }}"
          autocomplete="off"
          value="">
        @error('due_at')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
      </div>
    </div>

    {{-- ارجاع به --}}
    <div>
      <label class="block text-sm mb-1">ارجاع به</label>
      <select name="assigned_to_id" class="w-full rounded-md border p-2" required>
        @foreach($users as $u)
          <option value="{{ $u->id }}">{{ $u->name }}</option>
        @endforeach
      </select>
    </div>

    {{-- مربوط به (انتخاب با مودال‌ها) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">مربوط به</label>
        <div class="flex gap-2">
          <button type="button" onclick="openContactModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200">مخاطب +</button>
          <button type="button" onclick="openOrganizationModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200">سازمان +</button>
        </div>
      </div>
      <div>
        <label class="block text-sm mb-1">آیتم انتخاب‌شده</label>
        <input id="related_display" type="text" class="w-full rounded-md border p-2 bg-gray-50" placeholder="— انتخاب نشده —" readonly>
      </div>
    </div>

    {{-- فیلدهای واقعی فرم برای مربوط به --}}
    <input type="hidden" name="related_type" id="related_type" value="{{ old('related_type') }}">
    <input type="hidden" name="related_id"   id="related_id"   value="{{ old('related_id') }}">
    @error('related_type')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    @error('related_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

    {{-- وضعیت / اولویت --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">وضعیت</label>
        <select name="status" class="w-full rounded-md border p-2" required>
          <option value="not_started">شروع نشده</option>
          <option value="in_progress">در حال انجام</option>
          <option value="completed">تکمیل شده</option>
          <option value="scheduled">برنامه‌ریزی شده</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">اولویت</label>
        <select name="priority" class="w-full rounded-md border p-2" required>
          <option value="normal">معمولی</option>
          <option value="medium">متوسط</option>
          <option value="high">زیاد</option>
        </select>
      </div>
    </div>

    {{-- توضیحات --}}
    <div>
      <label class="block text-sm mb-1">توضیحات</label>
      <textarea name="description" rows="4" class="w-full rounded-md border p-2"></textarea>
    </div>

    {{-- خصوصی --}}
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_private" value="1">
      <span>خصوصی (عدم نمایش برای سایر کاربران)</span>
    </label>

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

@vite(['resources/js/activities/create.js'])
