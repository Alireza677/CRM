@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [['title' => 'یافتن موارد تکراری']];
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">سازمان‌های تکراری</h2>
                <p class="text-sm text-gray-500 mt-1">تعداد گروه‌های شناسایی‌شده: {{ $groups->total() }}</p>
            </div>

            <form method="POST" action="{{ route('sales.organizations.duplicates.scan') }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                    اسکن موارد تکراری
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">کلید تطبیق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">مقدار تطبیق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تعداد رکوردها</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">آخرین بروزرسانی</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($groups as $group)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $group->match_key }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $group->match_value }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $group->items_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $group->updated_at }}</td>
                                <td class="px-4 py-3 text-sm text-blue-600">
                                    <a href="{{ route('sales.organizations.duplicates.review', $group) }}" class="hover:underline">
                                        بررسی و ادغام
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                    هیچ گروه تکراری پیدا نشد. برای ساخت گروه‌ها، یک بار «اسکن موارد تکراری» را اجرا کنید.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $groups->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
