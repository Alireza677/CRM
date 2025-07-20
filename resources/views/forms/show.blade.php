@extends('layouts.app')

@section('content')
    
        @section('header')
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    جزئیات فرم
                </h2>
                <div class="flex space-x-4 space-x-reverse">
                    <a href="{{ route('forms.edit', $form) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        ویرایش
                    </a>
                    <form action="{{ route('forms.destroy', $form) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700" onclick="return confirm('آیا از حذف این فرم اطمینان دارید؟')">
                            حذف
                        </button>
                    </form>
                </div>
            </div>
        @endsection

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Type -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">نوع فرم</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ Form::getTypes()[$form->type] }}</p>
                            </div>

                            <!-- Title -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">عنوان</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $form->title }}</p>
                            </div>

                            <!-- Supplier -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">تأمین‌کننده</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $form->supplier->name ?? 'نامشخص' }}</p>
                            </div>

                            <!-- Purchase Date -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">تاریخ خرید</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $form->purchase_date->format('Y/m/d') }}</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">وضعیت</h3>
                                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($form->status === 'approved') bg-green-100 text-green-800
                                    @elseif($form->status === 'rejected') bg-red-100 text-red-800
                                    @elseif($form->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ Form::getStatuses()[$form->status] }}
                                </span>
                            </div>

                            <!-- Total -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">مجموع</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ number_format($form->total) }} تومان</p>
                            </div>

                            <!-- Assigned To -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">ارجاع به</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $form->assignedUser->name ?? 'نامشخص' }}</p>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900">توضیحات</h3>
                                <p class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $form->description ?? 'بدون توضیحات' }}</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('forms.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                بازگشت به لیست
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
@endsection 