@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'تنظیمات']
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 text-right">
                    
                    <div class="flex justify-between items-center mb-6">
                        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">
                            ← بازگشت به داشبورد
                        </a>
                        <h1 class="text-2xl font-semibold">تنظیمات</h1>
                    </div>

                    <div class="space-y-4">
                        <a href="{{ route('settings.users.index') }}" 
                           class="block w-full text-right text-blue-600 hover:underline text-lg">
                            مدیریت کاربران →
                        </a>
                        {{-- در آینده می‌توان آیتم‌های دیگری اضافه کرد --}}
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
@endsection
