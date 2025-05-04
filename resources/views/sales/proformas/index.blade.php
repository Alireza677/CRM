<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('پیش‌فاکتورها') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search Bar -->
            <div class="mb-4">
                <form action="{{ route('sales.proformas.index') }}" method="GET" class="flex items-center">
                    <x-text-input
                        type="text"
                        name="search"
                        class="block w-full"
                        placeholder="{{ __('جستجو در موضوع یا نام سازمان...') }}"
                        value="{{ request('search') }}"
                    />
                    <x-primary-button class="mr-4">
                        {{ __('جستجو') }}
                    </x-primary-button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ route('sales.proformas.index', ['sort' => 'subject', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                        {{ __('موضوع') }}
                                        @if(request('sort') === 'subject')
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('مرحله') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ route('sales.proformas.index', ['sort' => 'organization_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                        {{ __('سازمان') }}
                                        @if(request('sort') === 'organization_name')
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('مخاطب') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ route('sales.proformas.index', ['sort' => 'total_amount', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                        {{ __('مبلغ کل') }}
                                        @if(request('sort') === 'total_amount')
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ route('sales.proformas.index', ['sort' => 'proforma_date', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                        {{ __('تاریخ پیش‌فاکتور') }}
                                        @if(request('sort') === 'proforma_date')
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('فرصت') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ route('sales.proformas.index', ['sort' => 'assigned_to_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                        {{ __('ارجاع به') }}
                                        @if(request('sort') === 'assigned_to_name')
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($proformas as $proforma)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <button class="text-gray-400 hover:text-yellow-500 focus:outline-none">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            </button>
                                            <span class="mr-2">{{ $proforma->subject }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $stageColors = [
                                                'created' => 'bg-blue-100 text-blue-800',
                                                'accepted' => 'bg-green-100 text-green-800',
                                                'delivered' => 'bg-purple-100 text-purple-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'expired' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $stageLabels = [
                                                'created' => 'ایجاد شده',
                                                'accepted' => 'تایید شده',
                                                'delivered' => 'تحویل شده',
                                                'rejected' => 'رد شده',
                                                'expired' => 'منقضی شده'
                                            ];
                                            $color = $stageColors[$proforma->stage] ?? 'bg-gray-100 text-gray-800';
                                            $label = $stageLabels[$proforma->stage] ?? $proforma->stage;
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $proforma->organization_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $proforma->contact_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ number_format($proforma->total_amount) }} تومان
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ jdate($proforma->proforma_date)->format('Y/m/d') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $proforma->opportunity_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $proforma->assigned_to_name }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $proformas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 