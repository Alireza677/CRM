@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">ایجاد پروژه جدید</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
            <ul class="list-disc pr-6">
                @foreach ($errors->all() as $error)
                    <li class="mb-1">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('projects.store') }}" method="POST" class="bg-white rounded shadow p-6">
        @csrf

        {{-- نام پروژه --}}
        <div class="mb-4">
            <label class="block mb-1 font-medium">نام پروژه <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full border rounded p-2 focus:outline-none focus:ring"
                placeholder="مثلاً: بهینه‌سازی گرمایش سالن A">
        </div>

        {{-- تاریخ‌ها --}}
        <div class="mb-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block mb-1 font-medium">تاریخ شروع</label>
                <input type="text" id="start_date_shamsi" name="start_date_shamsi"
                       class="persian-datepicker w-full border rounded p-2 focus:outline-none focus:ring"
                       data-alt-field="start_date" placeholder="YYYY/MM/DD" autocomplete="off"
                       value="{{ old('start_date_shamsi') }}">
                <input type="hidden" id="start_date" name="start_date" value="{{ old('start_date') }}">
                @error('start_date') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block mb-1 font-medium">موعد مقرر</label>
                <input type="text" id="due_date_shamsi" name="due_date_shamsi"
                       class="persian-datepicker w-full border rounded p-2 focus:outline-none focus:ring"
                       data-alt-field="due_date" placeholder="YYYY/MM/DD" autocomplete="off"
                       value="{{ old('due_date_shamsi') }}">
                <input type="hidden" id="due_date" name="due_date" value="{{ old('due_date') }}">
                @error('due_date') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>
        </div>
        
        {{-- مسئول پروژه --}}
        <div class="mb-4">
            <label class="block mb-1 font-medium">مسئول پروژه <span class="text-red-500">*</span></label>
            <select name="manager_id" required class="w-full border rounded p-2 focus:outline-none focus:ring">
                <option value="">-- انتخاب کاربر --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string)old('manager_id')===(string)$user->id ? 'selected' : '' }}>
                        {{ $user->name ?? $user->email }}
                    </option>
                @endforeach
            </select>
            @error('manager_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- اعضای پروژه (چندنفره با چک‌باکس) --}}
        <div class="mb-6">
            <label class="block mb-1 font-medium">اعضای پروژه</label>

            <div class="border rounded p-3 max-h-56 overflow-y-auto grid gap-2 md:grid-cols-2">
                @foreach($users as $user)
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="members[]"
                            value="{{ $user->id }}"
                            @checked(collect(old('members', []))->contains($user->id))
                            class="h-4 w-4"
                        >
                        <span class="text-sm">
                            {{ $user->name ?? $user->email }}
                        </span>
                    </label>
                @endforeach
            </div>

            <p class="text-gray-500 text-sm mt-1">
                می‌توانید چند نفر انتخاب کنید. (مدیر پروژه به‌صورت خودکار به اعضا اضافه می‌شود.)
            </p>
        </div>


        {{-- توضیحات --}}
        <div class="mb-6">
            <label class="block mb-1 font-medium">توضیحات</label>
            <textarea name="description" rows="4"
                    class="w-full border rounded p-2 focus:outline-none focus:ring"
                    placeholder="توضیح کوتاه درباره پروژه...">{{ old('description') }}</textarea>
        </div>


        <div class="flex gap-3">
            <a href="{{ route('projects.index') }}" class="px-4 py-2 rounded bg-gray-200">بازگشت</a>
            <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                ذخیره پروژه
            </button>
        </div>
    </form>

</div>
@endsection
