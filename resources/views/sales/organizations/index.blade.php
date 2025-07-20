@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'سازمان‌ها']
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                {{ __('سازمان‌ها') }}
            </h2>

            <!-- Search Bar -->
            <div class="mb-4">
                <form method="GET" action="{{ route('sales.organizations.index') }}" class="flex gap-2">
                    <input type="text" 
                        name="search" 
                        class="flex-1" 
                        placeholder="جستجو بر اساس نام سازمان یا شماره تلفن..."
                        value="{{ request('search') }}"
                    >
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">جستجو</button>
                </form>
            </div>

            <!-- Organizations Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">لیست سازمان‌ها</h3>
                        <a href="{{ route('sales.organizations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            ایجاد سازمان جدید
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام سازمان</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">صنعت</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شماره تلفن</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ایمیل</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وب‌سایت</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ارجاع به</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($organizations as $organization)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <i class="fas fa-building text-gray-400 text-2xl"></i>
                                                </div>
                                                <div class="mr-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $organization->name }}
                                                    </div>
                                                </div>
                                                @if($organization->is_favorite)
                                                    <i class="fas fa-star text-yellow-400"></i>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $organization->industry ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $organization->phone ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $organization->email ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            @if($organization->website)
                                                <a href="{{ $organization->website }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                    {{ $organization->website }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $organization->assigned_to_name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ jdate($organization->created_at, 'Y/m/d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $organizations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
