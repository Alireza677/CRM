@extends('layouts.app')

@section('content')
    <div class="p-6 bg-white rounded shadow">
        <h1 class="text-xl font-bold mb-4">لیست اسناد</h1>

        <div class="mb-4 text-left">
            <a href="{{ route('sales.documents.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                + ثبت سند جدید
            </a>
        </div>

        @if ($documents->count())
        <ul class="space-y-2">
    @foreach ($documents as $doc)
        <li class="flex justify-between items-center border rounded p-3 hover:shadow-sm bg-white">
            <div>
                <div class="font-medium text-gray-800">{{ $doc->title ?? 'بدون عنوان' }}</div>
                <div class="text-sm text-gray-500 mt-1">
                    @if ($doc->opportunity)
                        مربوط به فرصت: <a href="{{ route('sales.opportunities.show', $doc->opportunity->id) }}" class="text-blue-600 hover:underline">
                        {{ $doc->opportunity->name }}
                            </a>
                    @else
                        بدون ارتباط با فرصت فروش
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="text-gray-600 hover:text-blue-600" title="مشاهده">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ asset('storage/' . $doc->file_path) }}" download class="text-gray-600 hover:text-green-600" title="دانلود">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </li>
    @endforeach
</ul>

        @else
            <p class="text-gray-500">هیچ سندی وجود ندارد.</p>
        @endif
    </div>
@endsection
