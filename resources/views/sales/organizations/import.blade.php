@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto mt-10 bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4 text-right">ایمپورت سازمان‌ها از فایل Excel</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-800 p-3 rounded mb-4">
                <ul class="list-disc mr-4">
                    @foreach($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sales.organizations.import') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">فایل Excel را انتخاب کنید:</label>
                <input type="file" name="file" id="file" required
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" accept=".xlsx,.csv">
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                شروع ایمپورت
            </button>
        </form>
    </div>
@endsection
