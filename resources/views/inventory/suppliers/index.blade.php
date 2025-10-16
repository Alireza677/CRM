@extends('layouts.app')
@php
    $breadcrumb = [
        ['title' => 'تأمین‌کنندگان']
    ];
@endphp

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">{{ __('تأمین‌کنندگان') }}</h2>
            <a href="{{ route('inventory.suppliers.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                ایجاد تأمین‌کننده
            </a>
        </div>

        <div class="mb-4 -mt-2">
            <a href="{{ route('inventory.suppliers.import') }}"
               class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                ایمپورت تأمین‌کنندگان
            </a>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <div class="p-6">
                <!-- Search Bar -->
                <div class="mb-4">
                    <form action="{{ route('inventory.suppliers.index') }}" method="GET" class="flex gap-4">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="جستجو در همه فیلدها...">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                            جستجو
                        </button>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تلفن</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ایمیل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">دسته‌بندی</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ارجاع به</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($suppliers as $supplier)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $supplier->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $supplier->phone }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $supplier->email }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $supplier->category_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $supplier->assigned_to_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 space-x-2 space-x-reverse">
                                        <a href="{{ route('inventory.suppliers.show', $supplier->id) }}"
                                           class="text-blue-600 hover:underline">مشاهده</a>
                                        <a href="{{ route('inventory.suppliers.edit', $supplier->id) }}"
                                           class="text-yellow-600 hover:underline">ویرایش</a>
                                        <form action="{{ route('inventory.suppliers.destroy', $supplier->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline"
                                                    onclick="return confirm('آیا مطمئن هستید؟')">
                                                حذف
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm text-gray-500 text-center">
                                        هیچ تأمین‌کننده‌ای یافت نشد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $suppliers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
