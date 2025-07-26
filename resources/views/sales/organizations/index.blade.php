@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [['title' => 'سازمان‌ها']];
    $sort = request('sort', 'created_at');
    $direction = request('direction', 'desc');
    $opposite = $direction === 'asc' ? 'desc' : 'asc';
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">لیست سازمان‌ها</h2>

        {{-- دکمه ایجاد + فرم جستجو --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
            {{-- فرم جستجو --}}
            <form method="GET" action="{{ route('sales.organizations.index') }}">
                <div class="flex gap-3">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="جستجو بر اساس نام یا تلفن..."
                        class="w-64 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        جستجو
                    </button>
                </div>
            </form>

            {{-- دکمه ایجاد سازمان جدید --}}
            <a href="{{ route('sales.organizations.create') }}"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 whitespace-nowrap">
                + ایجاد سازمان جدید
            </a>
        </div>

        {{-- جدول --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 text-right">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">
                            <a href="{{ route('sales.organizations.index', ['sort' => 'name', 'direction' => $sort === 'name' ? $opposite : 'asc']) }}">
                                نام سازمان
                                @if ($sort === 'name')
                                    {!! $direction === 'asc' ? '↑' : '↓' !!}
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2">شماره تلفن</th>
                        <th class="px-4 py-2">شهر</th>
                        <th class="px-4 py-2">
                            <a href="{{ route('sales.organizations.index', ['sort' => 'assigned_to_name', 'direction' => $sort === 'assigned_to_name' ? $opposite : 'asc']) }}">
                                ارجاع‌شده به
                                @if ($sort === 'assigned_to_name')
                                    {!! $direction === 'asc' ? '↑' : '↓' !!}
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($organizations as $organization)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('sales.organizations.show', $organization) }}" class="text-indigo-600 hover:underline">
                                    {{ $organization->name }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $organization->phone }}</td>
                            <td class="px-4 py-2">{{ $organization->city }}</td>
                            <td class="px-4 py-2">{{ $organization->assigned_to_name }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('sales.organizations.edit', $organization) }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21H4a1 1 0 01-1-1V4a1 1 0 011-1h12a1 1 0 011 1v6"/>
                                    </svg>
                                    ویرایش
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">هیچ سازمانی پیدا نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="mt-4">
            {{ $organizations->links() }}
        </div>
    </div>
</div>
@endsection
