<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('فروش') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Sales Overview -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">خلاصه فروش</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">فروش این ماه</span>
                                    <span class="font-medium text-gray-800">۱۲,۵۰۰,۰۰۰ ریال</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">فرصت‌های فعال</span>
                                    <span class="font-medium text-gray-800">۱۵</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">عملیات سریع</h3>
                            <div class="space-y-4">
                                <a href="{{ route('sales.opportunities.index') }}" 
                                   class="block w-full text-right px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    فرصت‌های فروش
                                </a>
                                <a href="{{ route('sales.contacts.index') }}" 
                                   class="block w-full text-right px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    مخاطبین
                                </a>
                                <a href="{{ route('sales.organizations.index') }}" 
                                   class="block w-full text-right px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    سازمان‌ها
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 