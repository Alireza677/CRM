<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('بازاریابی') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Marketing Campaigns -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">کمپین‌های بازاریابی</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-800">کمپین بهار ۱۴۰۳</h4>
                                        <p class="text-sm text-gray-600">تاریخ شروع: ۱ فروردین</p>
                                    </div>
                                    <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">فعال</span>
                                </div>
                            </div>
                        </div>

                        <!-- Marketing Analytics -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">تحلیل‌های بازاریابی</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">نرخ تبدیل</span>
                                    <span class="font-medium text-gray-800">۲.۵٪</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">بازدیدکنندگان</span>
                                    <span class="font-medium text-gray-800">۱,۲۳۴</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 