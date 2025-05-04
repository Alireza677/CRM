<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('مخاطبین') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search Bar -->
            <div class="mb-4">
                <form method="GET" action="{{ route('sales.contacts.index') }}" class="flex gap-2">
                    <x-text-input 
                        type="text" 
                        name="search" 
                        class="flex-1" 
                        placeholder="جستجو بر اساس نام یا شماره موبایل..."
                        value="{{ request('search') }}"
                    />
                    <x-primary-button>
                        جستجو
                    </x-primary-button>
                </form>
            </div>

            <!-- Contacts Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('sales.contacts.index', array_merge(request()->query(), ['sort' => 'first_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                            نام
                                            @if(request('sort') === 'first_name')
                                                <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('sales.contacts.index', array_merge(request()->query(), ['sort' => 'mobile', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                            شماره موبایل
                                            @if(request('sort') === 'mobile')
                                                <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('sales.contacts.index', array_merge(request()->query(), ['sort' => 'organization_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                            سازمان
                                            @if(request('sort') === 'organization_name')
                                                <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('sales.contacts.index', array_merge(request()->query(), ['sort' => 'assigned_to_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                            ارجاع به
                                            @if(request('sort') === 'assigned_to_name')
                                                <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('sales.contacts.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                            تاریخ ایجاد
                                            @if(request('sort') === 'created_at')
                                                <i class="fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
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
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $contact->gender === 'male' ? 'آقای' : 'خانم' }} {{ $contact->first_name }} {{ $contact->last_name }}
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
                                            {{ jdate($contact->created_at)->format('Y/m/d H:i') }}
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
</x-app-layout> 