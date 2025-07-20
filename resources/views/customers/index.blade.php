@extends('layouts.app')

@section('content')
    
        @section('header')
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('لیست مشتریان') }}
                </h2>
                <a href="{{ route('customers.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    ثبت مشتری جدید
                </a>
            </div>
        @endsection

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        @if (session('success'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline">{{ session('success') }}</span>
                            </div>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">نام</th>
                                        <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">ایمیل</th>
                                        <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">تلفن</th>
                                        <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customers as $customer)
                                        <tr>
                                            <td class="px-6 py-4 border-b border-gray-200">{{ $customer->name }}</td>
                                            <td class="px-6 py-4 border-b border-gray-200">{{ $customer->email }}</td>
                                            <td class="px-6 py-4 border-b border-gray-200">{{ $customer->phone ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 border-b border-gray-200">
                                                <div class="flex space-x-2 justify-end">
                                                    <a href="{{ route('customers.edit', $customer) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                        ویرایش
                                                    </a>
                                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('آیا از حذف این مشتری اطمینان دارید؟')">
                                                            حذف
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 border-b border-gray-200 text-center">مشتری یافت نشد.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
@endsection 