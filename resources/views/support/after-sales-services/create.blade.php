@extends('layouts.app')

@section('header')
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-semibold text-gray-800">ثبت فرم خدمات پس از فروش</h2>
        <p class="text-sm text-gray-500">اطلاعات مشتری و مشکل دستگاه را وارد کنید.</p>
    </div>
@endsection

@section('content')
    <div class="py-8" dir="rtl">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                <form action="{{ route('support.after-sales-services.store') }}" method="POST" class="space-y-8">
                    @csrf

                    @include('support.after-sales-services.partials.form-fields')

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('support.after-sales-services.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                            انصراف
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                            ذخیره فرم
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

