@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form action="{{ route('inventory.suppliers.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- اطلاعات تامین کننده -->
                <h2 class="text-lg font-semibold text-gray-800">اطلاعات تأمین‌کننده</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">نام تأمین‌کننده <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" value="{{ old('name') }}">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">ایمیل</label>
                        <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('email') }}">
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">دسته‌بندی</label>
                        <input type="text" name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('category') }}">
                    </div>

                    <div>
                        <label for="telegram_id" class="block text-sm font-medium text-gray-700">آیدی تلگرام</label>
                        <input type="text" name="telegram_id" id="telegram_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('telegram_id') }}">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">تلفن</label>
                        <input type="text" name="phone" id="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('phone') }}">
                    </div>

                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700">وب‌سایت</label>
                        <input type="url" name="website" id="website" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('website') }}">
                    </div>

                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به <span class="text-red-500">*</span></label>
                        <select name="assigned_to" id="assigned_to" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">انتخاب کاربر</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- اطلاعات آدرس -->
                <h2 class="text-lg font-semibold text-gray-800">اطلاعات آدرس</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700">استان</label>
                        <input type="text" name="province" id="province" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('province') }}">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">شهر</label>
                        <input type="text" name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="{{ old('city') }}">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">آدرس</label>
                        <textarea name="address" id="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700">کد پستی</label>
                        <textarea name="postal_code" id="postal_code" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('postal_code') }}</textarea>
                    </div>
                </div>

                <!-- توضیحات -->
                <h2 class="text-lg font-semibold text-gray-800">توضیحات</h2>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">توضیحات</label>
                    <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description') }}</textarea>
                </div>

                <!-- Footer -->
                <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                    <a href="{{ route('inventory.suppliers.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm rounded-md bg-white hover:bg-gray-100 text-gray-700">لغو</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
