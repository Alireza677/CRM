@extends('layouts.app')

@section('content')
    
        @section('header')
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('داشبورد') }}
            </h2>
        @endsection

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Sample Cards -->
                            <div class="bg-blue-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">آمار کلی</h3>
                                <p class="text-gray-600">اطلاعات و آمار کلی سیستم</p>
                            </div>
                            
                            <div class="bg-green-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">فعالیت‌های اخیر</h3>
                                <p class="text-gray-600">لیست آخرین فعالیت‌های انجام شده</p>
                            </div>
                            
                            <div class="bg-purple-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">اعلانات</h3>
                                <p class="text-gray-600">اطلاعیه‌ها و پیام‌های جدید</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
@endsection 