@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'تنظیمات', 'url' => route('settings.index')],
        ['title' => 'درصد کمیسیون نقش‌ها'],
    ];
@endphp

<div class="max-w-4xl mx-auto mt-10">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800">درصد کمیسیون نقش‌ها</h1>
            <a href="{{ route('settings.index') }}" class="text-blue-600 hover:underline text-sm">بازگشت به تنظیمات</a>
        </div>

        @if(session('status'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('settings.commissions.update') }}" class="space-y-6">
            @csrf

            <div class="space-y-4">
                @foreach($rolePercents as $roleKey => $value)
                    @php
                        $meta = $roleMeta[$roleKey] ?? ['label' => $roleKey, 'description' => null];
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start border border-gray-100 rounded-lg p-4">
                        <div class="md:col-span-2">
                            <div class="text-sm font-semibold text-gray-800">{{ $meta['label'] }}</div>
                            @if(!empty($meta['description']))
                                <div class="text-xs text-gray-500 mt-1">{{ $meta['description'] }}</div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1" for="role_{{ $roleKey }}">درصد</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                id="role_{{ $roleKey }}"
                                name="role_percents[{{ $roleKey }}]"
                                class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('role_percents.' . $roleKey, $value) }}"
                            >
                            @error('role_percents.' . $roleKey)
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t pt-4 flex items-center justify-between">
                <p class="text-xs text-gray-500">این درصدها در محاسبات کمیسیون فرصت‌های فروش اعمال می‌شوند.</p>
                <button type="submit" class="bg-blue-600 text-white text-sm rounded px-4 py-2 hover:bg-blue-700">ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>
@endsection
