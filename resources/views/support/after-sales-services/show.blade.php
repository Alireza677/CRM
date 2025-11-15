@extends('layouts.app')

@section('header')
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-semibold text-gray-800">جزئیات فرم خدمات پس از فروش</h2>
        <p class="text-sm text-gray-500">ثبت شده در {{ $afterSalesService->created_at ? jdate($afterSalesService->created_at)->format('Y/m/d H:i') : '' }}</p>
    </div>
@endsection

@section('content')
    <div class="py-8" dir="rtl">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-gray-500 mb-1">نام مشتری</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $afterSalesService->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-1">مسئول هماهنگ‌کننده</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $afterSalesService->coordinator_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-1">شماره همراه هماهنگ‌کننده</p>
                        <p class="text-lg font-semibold text-gray-900" dir="ltr">
                            {{ $afterSalesService->coordinator_mobile }}
                        </p>
                    </div>
                    @if($afterSalesService->creator)
                        <div>
                            <p class="text-gray-500 mb-1">ثبت‌کننده</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $afterSalesService->creator->name }}</p>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">آدرس</h3>
                    <p class="text-gray-700 leading-relaxed">{{ $afterSalesService->address }}</p>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">شرح مشکل دستگاه</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $afterSalesService->issue_description }}</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-4 border-t border-gray-100">
                    <div class="flex gap-3">
                        <a href="{{ route('support.after-sales-services.edit', $afterSalesService) }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50">
                            ویرایش فرم
                        </a>
                        <form action="{{ route('support.after-sales-services.destroy', $afterSalesService) }}" method="POST"
                              onsubmit="return confirm('آیا از حذف این فرم اطمینان دارید؟')" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">
                                حذف فرم
                            </button>
                        </form>
                    </div>

                    <a href="{{ route('support.after-sales-services.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                        « بازگشت به لیست
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
