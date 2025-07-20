@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ایجاد فرصت جدید']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">
            {{ __('فرصت جدید') }}
        </h2>

        <form method="POST" action="{{ route('sales.opportunities.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- ستون ۱ --}}
                <div>
                    <label for="name" class="block font-medium text-sm text-gray-700 required">عنوان</label>
                    <input id="name" name="name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           value="{{ old('name') }}" required>
                    @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="organization_id" class="block font-medium text-sm text-gray-700">سازمان</label>
                    <select id="organization_id" name="organization_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" {{ old('organization_id') == $organization->id ? 'selected' : '' }}>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('organization_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="contact_id" class="block font-medium text-sm text-gray-700">مخاطب</label>
                    <select id="contact_id" name="contact_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                {{ $contact->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('contact_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="type" class="block font-medium text-sm text-gray-700 ">نوع کسب‌وکار</label>
                    <select id="type" name="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        <option value="کسب و کار موجود" {{ old('type') == 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
                        <option value="کسب و کار جدید" {{ old('type') == 'کسب و کار جدید' ? 'selected' : '' }}>کسب و کار جدید</option>
                    </select>
                    @error('type') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="stage" class="block font-medium text-sm text-gray-700 required">مرحله فروش</label>
                    <select name="stage" id="stage" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید...</option>
                        <option value="در حال پیگیری" {{ old('stage') == 'در حال پیگیری' ? 'selected' : '' }}>در حال پیگیری</option>
                        <option value="پیگیری در آینده" {{ old('stage') == 'پیگیری در آینده' ? 'selected' : '' }}>پیگیری در آینده</option>
                        <option value="برنده" {{ old('stage') == 'برنده' ? 'selected' : '' }}>برنده</option>
                        <option value="بازنده" {{ old('stage') == 'بازنده' ? 'selected' : '' }}>بازنده</option>
                        <option value="سرکاری" {{ old('stage') == 'سرکاری' ? 'selected' : '' }}>سرکاری</option>
                        <option value="ارسال پیش فاکتور" {{ old('stage') == 'ارسال پیش فاکتور' ? 'selected' : '' }}>ارسال پیش فاکتور</option>
                    </select>
                    @error('stage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="source" class="block font-medium text-sm text-gray-700 required">منبع سرنخ</label>
                    <select id="source" name="source" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        <option value="وب سایت" {{ old('source') == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                        <option value="مشتریان قدیمی" {{ old('source') == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                        <option value="نمایشگاه" {{ old('source') == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                        <option value="بازاریابی حضوری" {{ old('source') == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                    </select>
                    @error('source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="assigned_to" class="block font-medium text-sm text-gray-700 required">ارجاع به</label>
                    <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="success_rate" class="block font-medium text-sm text-gray-700 ">درصد موفقیت</label>
                    <input id="success_rate" name="success_rate" type="number" min="0" max="100"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           value="{{ old('success_rate') }}" required>
                    @error('success_rate') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="amount" class="block font-medium text-sm text-gray-700 ">مبلغ</label>
                    <input id="amount" name="amount" type="number" min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           value="{{ old('amount') }}" required>
                    @error('amount') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="next_follow_up" class="block font-medium text-sm text-gray-700">تاریخ پیگیری بعدی</label>
                    <input type="text" id="next_follow_up_shamsi" class="form-control" placeholder="انتخاب تاریخ ">
                    <input type="hidden" name="next_follow_up" id="next_follow_up" value="{{ old('next_follow_up') }}">
                    @error('next_follow_up') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block font-medium text-sm text-gray-700">توضیحات</label>
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                    @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    ذخیره
                </button>
            </div>
        </form>
    </div>
</div>

{{-- استایل ستاره قرمز برای فیلدهای الزامی --}}
<style>
    label.required::after {
        content: ' *';
        color: red;
    }
</style>
@endsection
