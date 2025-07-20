@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'مخاطبین']
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                {{ __('مخاطبین') }}
            </h2>

            <!-- Create New Contact Button -->
            <div class="mb-4">
                <a href="{{ route('sales.contacts.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-plus mr-2"></i>
                    ایجاد مخاطب جدید
                </a>
            </div>

            <!-- Search Bar -->
            <div class="mb-4">
                <form method="GET" action="{{ route('sales.contacts.index') }}" class="flex gap-2">
                    <input type="text" 
                        name="search" 
                        class="flex-1" 
                        placeholder="جستجو بر اساس نام یا شماره موبایل..."
                        value="{{ request('search') }}"
                    >
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">جستجو</button>
                </form>
            </div>

            <!-- Contacts Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شماره موبایل</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">سازمان</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ارجاع به</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($contacts as $contact)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <i class="fas fa-user-circle text-gray-400 text-2xl"></i>
                                                </div>
                                                <div class="mr-4">
                                                <div class="text-sm font-medium text-blue-600 hover:underline">
                                                <a href="{{ route('sales.contacts.show', $contact->id) }}"
    class="text-sm font-medium text-blue-600 hover:underline">
    {{ $contact->first_name }} {{ $contact->last_name }}
</a>


                                                </div>

                                                </div>
                                                @if($contact->is_favorite)
                                                    <i class="fas fa-star text-yellow-400"></i>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $contact->mobile }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $contact->organization_name ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $contact->assigned_to_name ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $contact->created_at->format('Y/m/d H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $contacts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
