@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'داشبورد', 'url' => route('dashboard')],
        ['title' => 'مخاطبین', 'url' => route('sales.contacts.index')],
        ['title' => $contact->first_name . ' ' . $contact->last_name],
    ];
@endphp

<div class="max-w-5xl mx-auto py-8 flex flex-col gap-5" dir="rtl">
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">جزئیات مخاطب</h2>

        {{-- اطلاعات پایه --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm text-gray-700">
        <div><span class="font-semibold">نام:</span> {{ $contact->first_name }} {{ $contact->last_name }}</div>
        <div><span class="font-semibold">ایمیل:</span> {{ $contact->email ?? '—' }}</div>
        <div><span class="font-semibold">تلفن:</span> {{ $contact->phone ?? '—' }}</div>
        <div><span class="font-semibold">موبایل:</span> {{ $contact->mobile ?? '—' }}</div>

        <div><span class="font-semibold">شهر:</span> {{ $contact->city ?? '—' }}</div>

        <div>
            <span class="font-semibold">سازمان:</span>
            @if($contact->organization)
                <a href="{{ route('sales.organizations.show', $contact->organization->id) }}" class="text-indigo-600 hover:underline">
                    {{ $contact->organization->name }}
                </a>
            @else
                — 
            @endif
        </div>

        <div class="sm:col-span-2"><span class="font-semibold">آدرس:</span> {{ $contact->address ?? '—' }}</div>

        @if($contact->opportunity)
            <div class="sm:col-span-2">
                <span class="font-semibold">مربوط به فرصت فروش:</span>
                <a href="{{ route('sales.opportunities.show', $contact->opportunity->id) }}" class="text-blue-600 hover:underline">
                    {{ $contact->opportunity->name }}
                </a>
            </div>
        @endif
    </div>


        {{-- دکمه‌ها --}}
        <div class="mt-6 flex justify-between items-center">
            <a href="{{ route('sales.contacts.index') }}" class="text-sm text-blue-600 hover:underline">
                ← بازگشت به لیست مخاطبین
            </a>
            <a href="{{ route('sales.contacts.edit', $contact->id) }}" class="text-sm text-green-600 hover:underline">
                ویرایش مخاطب
            </a>
        </div>
    </div>

    {{-- فرصت‌های فروش مرتبط --}}
    <div class="bg-white shadow rounded-lg p-6 mt-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">فرصت‌های فروش مرتبط</h3>

            <a href="{{ route('sales.opportunities.create', ['contact_id' => $contact->id]) }}"
            class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700 transition">
                ایجاد فرصت
            </a>
        </div>

        @if($contact->opportunities->count())
            <ul class="list-disc pr-5 text-sm text-gray-700">
            @foreach($contact->opportunities as $opportunity)
                <li>
                    <a href="{{ route('sales.opportunities.show', $opportunity->id) }}" class="text-blue-600 hover:underline">
                        {{ $opportunity->name }}
                    </a>
                    — <span class="text-gray-500">{{ jdate($opportunity->created_at)->format('Y/m/d') }}</span>
                </li>
            @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500">فرصت فروشی برای این مخاطب ثبت نشده است.</p>
        @endif
    </div>


    {{-- پیش‌فاکتورهای مرتبط --}}
<div class="bg-white shadow rounded-lg p-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">پیش‌فاکتورها</h3>

        <a href="{{ route('sales.proformas.create', ['contact_id' => $contact->id]) }}"
           class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700 transition">
            ایجاد پیش‌فاکتور
        </a>
    </div>

    @if($contact->proformas->count())
        <ul class="list-disc pr-5 text-sm text-gray-700">
            @foreach($contact->proformas as $proforma)
                <li>
                    <a href="{{ route('sales.proformas.show', $proforma->id) }}" class="text-blue-600 hover:underline">
                        پیش‌فاکتور شماره {{ $proforma->id }}
                    </a>
                    — <span class="text-gray-500">{{ jdate($proforma->created_at)->format('Y/m/d') }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500">پیش‌فاکتوری برای این مخاطب ثبت نشده است.</p>
    @endif
</div>

</div>
@endsection
