@extends('layouts.app')

@section('content')
    <div class="p-6 bg-white rounded shadow max-w-xl mx-auto">

        {{-- عنوان صفحه --}}
        <h1 class="text-xl font-bold mb-6 text-gray-700">ثبت سند جدید</h1>

        <form action="{{ route('sales.documents.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="opportunity_id" value="{{ $opportunityId }}">

            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">عنوان سند</label>
                <input type="text" name="title" id="title" class="mt-1 block w-full border-gray-300 rounded shadow-sm" required>
            </div>

            

            <div class="mb-6">
                <label for="file" class="block text-sm font-medium text-gray-700">فایل</label>
                <input type="file" name="file" id="file" class="mt-1 block w-full" required>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <a href="{{ route('sales.documents.index') }}"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                    انصراف
                </a>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    ذخیره سند
                </button>
            </div>
        </form>
    </div>
@endsection
