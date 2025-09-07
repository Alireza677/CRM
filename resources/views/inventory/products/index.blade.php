@extends('layouts.app')
@php
    $breadcrumb = [
        ['title' => 'محصولات']
    ];
@endphp

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <h2 class="text-2xl font-semibold mb-6 text-gray-800">
                        {{ __('محصولات') }}
                    </h2>

                    <div class="flex justify-between items-center mb-6">
                        <div class="flex-1 max-w-sm">
                            <form action="{{ route('inventory.products.index') }}" method="GET">
                                <div class="relative">
                                    <input type="text" name="search" value="{{ request('search') }}" 
                                        class="w-full pr-10 pl-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="{{ __('جستجو...') }}">
                                    <button type="submit" class="absolute left-0 inset-y-0 flex items-center pl-3">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="flex space-x-2 space-x-reverse">
                            {{-- دکمه ایمپورت محصولات --}}
                            <a href="{{ route('inventory.products.import') }}" 
                            class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                                📥 {{ __('ایمپورت محصولات') }}
                            </a>

                            {{-- دکمه افزودن محصول جدید --}}
                            <a href="{{ route('inventory.products.create') }}" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                ➕ {{ __('افزودن محصول جدید') }}
                            </a>
                        </div>
                    </div>


                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('نام محصول') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('کد محصول') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('شماره سریال') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('موجودی') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('قیمت واحد') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('تحویل گیرنده') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('درصد') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('وضعیت') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('دسته‌بندی') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ __('عملیات') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($products as $product)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $product->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $product->code }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $product->serial_number }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $product->stock_quantity }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($product->unit_price) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $product->receiver_name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $product->percentage ? $product->percentage . '%' : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $product->is_active ? __('فعال') : __('غیرفعال') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $product->category_name }}</td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <a href="#" class="text-indigo-600 hover:text-indigo-900">{{ __('ویرایش') }}</a>
                                            <a href="#" class="text-red-600 hover:text-red-900 mr-4">{{ __('حذف') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
@endsection
