@extends('layouts.app')

@section('header')
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-semibold text-gray-800">ویرایش فرم خدمات پس از فروش</h2>
        <p class="text-sm text-gray-500">اطلاعات زیر را ویرایش و ذخیره کنید.</p>
    </div>
@endsection

@section('content')
    <div class="py-8" dir="rtl">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                <form action="{{ route('support.after-sales-services.update', $afterSalesService) }}" method="POST" class="space-y-8">
                    @csrf
                    @method('PUT')

                    @include('support.after-sales-services.partials.form-fields', ['service' => $afterSalesService])

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('support.after-sales-services.show', $afterSalesService) }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                            بازگشت
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

