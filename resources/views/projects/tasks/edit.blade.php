@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4" dir="rtl">
    <h1 class="text-2xl font-bold mb-4">ویرایش تسک</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-red-700">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="space-y-4"
          action="{{ route('projects.tasks.update', [$project, $task]) }}"
          method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="block text-sm mb-1">عنوان</label>
            <input id="title" name="title" type="text"
                   value="{{ old('title', $task->title) }}"
                   class="w-full rounded-md border-gray-300">
            @error('title')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm mb-1">توضیحات</label>
            <textarea id="description" name="description" rows="4"
                      class="w-full rounded-md border-gray-300">{{ old('description', $task->description) }}</textarea>
            @error('description')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="priority" class="block text-sm mb-1">اولویت</label>
            <select id="priority" name="priority" class="w-full rounded-md border-gray-300">
                @foreach (['low'=>'کم','medium'=>'متوسط','high'=>'بالا'] as $val=>$label)
                    <option value="{{ $val }}" @selected(old('priority', $task->priority) === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('priority')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white">ذخیره</button>
            <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 rounded-md bg-gray-200">بازگشت</a>
        </div>
    </form>
</div>
@endsection