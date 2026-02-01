@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'تنظیمات', 'url' => route('settings.index')],
        ['title' => 'فروش', 'url' => route('settings.index')],
        ['title' => 'سرنخ‌ها'],
    ];
@endphp

<div class="max-w-4xl mx-auto mt-10">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800">تنظیمات سرنخ‌ها</h1>
            <a href="{{ route('settings.index') }}" class="text-blue-600 hover:underline text-sm">بازگشت به تنظیمات</a>
        </div>

        @if(session('status'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('settings.sales.leads.update') }}" class="space-y-6">
            @csrf

            <div class="border border-gray-100 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-800">کاربر جذب‌کننده شرکتی (Akhgar Tabesh)</div>
                <p class="text-xs text-gray-500 mt-1">برای منابع سازمانی، نقش جذب‌کننده به این کاربر اختصاص داده می‌شود.</p>
                <div class="mt-3">
                    <label for="company_acquirer_user_id" class="block text-sm text-gray-700 mb-1">انتخاب کاربر</label>
                    <select id="company_acquirer_user_id" name="company_acquirer_user_id" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">انتخاب کنید</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('company_acquirer_user_id', $companyAcquirerUserId) == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_acquirer_user_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border border-gray-100 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-800">منابع سازمانی (Company-owned)</div>
                <p class="text-xs text-gray-500 mt-1">برای این منابع، جذب‌کننده به‌صورت خودکار به کاربر شرکتی اختصاص داده می‌شود.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
                    @foreach($leadSources as $key => $label)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                name="company_sources[]"
                                value="{{ $key }}"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                @checked(in_array($key, old('company_sources', $companySources ?? []), true))
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('company_sources')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('company_sources.*')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t pt-4 flex items-center justify-between">
                <p class="text-xs text-gray-500">این تنظیمات فقط توسط ادمین قابل تغییر است.</p>
                <button type="submit" class="bg-blue-600 text-white text-sm rounded px-4 py-2 hover:bg-blue-700">ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>
@endsection
