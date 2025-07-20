@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8" dir="rtl">
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">جزئیات مخاطب</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm text-gray-700">
            <div>
                <span class="font-semibold">نام:</span>
                {{ $contact->first_name }} {{ $contact->last_name }}
            </div>

            <div>
                <span class="font-semibold">ایمیل:</span>
                {{ $contact->email ?? '—' }}
            </div>

            <div>
                <span class="font-semibold">تلفن:</span>
                {{ $contact->phone ?? '—' }}
            </div>

            <div>
                <span class="font-semibold">موبایل:</span>
                {{ $contact->mobile ?? '—' }}
            </div>

            <div>
                <span class="font-semibold">سازمان:</span>
                {{ $contact->organization->name ?? '—' }}
            </div>

            <div class="sm:col-span-2">
                <span class="font-semibold">آدرس:</span>
                {{ $contact->address ?? '—' }}
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('sales.contacts.index') }}" class="inline-block text-sm text-blue-600 hover:underline">
                ← بازگشت به لیست مخاطبین
            </a>
        </div>
    </div>
</div>
@endsection
