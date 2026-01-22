@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'پیش‌فاکتورها', 'url' => route('sales.proformas.index')],
        ['title' => 'ایمپورت پیش‌فاکتور']
    ];
@endphp

<h1>فرم ایمپورت</h1>

<div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-xl font-bold mb-4">ایمپورت پیش‌فاکتورها از فایل Excel</h2>

    {{-- راهنمای فرمت اکسل --}}
    <div class="bg-yellow-50 border border-yellow-300 p-4 rounded mb-6 text-sm text-right leading-relaxed">
        <p class="font-semibold text-yellow-800 mb-2">📋 راهنمای آماده‌سازی فایل اکسل برای ایمپورت پیش‌فاکتورها:</p>
        <ul class="list-disc list-inside text-gray-800 space-y-1">
            <li><code>subject</code> – موضوع پیش‌فاکتور</li>
            <li><code>proforma_stage</code> – مرحله پیش‌فاکتور (مثلاً: بررسی اولیه)</li>
            <li><code>proforma_date</code> – تاریخ پیش‌فاکتور (مثل: 2025-08-01)</li>
            <li><code>organization_name</code> – نام سازمان مرتبط</li>
            <li><code>contact_name</code> – نام مخاطب</li>
            <li><code>sales_opportunity</code> – عنوان فرصت فروش</li>
            <li><code>assigned_to</code> – نام کاربری یا نام شخصی کارمند ارجاع‌شده</li>
            <li><code>city</code> – شهر مشتری</li>
            <li><code>state</code> – استان مشتری</li>
            <li><code>customer_address</code> – آدرس کامل مشتری</li>
            <li><code>total_amount</code> – مبلغ نهایی پیش‌فاکتور</li>
        </ul>
        <p class="mt-2 text-xs text-gray-500">عنوان ستون‌ها باید دقیقاً مطابق بالا، به زبان انگلیسی، در ردیف اول فایل باشند.</p>
    </div>

    {{-- فرم ایمپورت --}}
    <form method="POST" action="{{ route('sales.proformas.import') }}" enctype="multipart/form-data">
        @csrf
        <label class="block mb-2 font-medium">فایل اکسل را انتخاب کنید:</label>
        <input type="file" name="file" class="border p-2 w-full mb-4" required accept=".xlsx,.csv">

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            شروع ایمپورت
        </button>
    </form>
</div>

{{-- پیام موفقیت با SweetAlert --}}
@if(session('success'))
    @if(!config('app.assets_emergency'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const message = @json(session('success'));
            if (window.Swal) {
                Swal.fire({
                icon: 'success',
                title: 'ایمپورت موفق!',
                html: '{{ session('success') }}<br><br>چه کاری می‌خواهید انجام دهید؟',
                showCancelButton: true,
                confirmButtonText: 'ایمپورت موارد جدید',
                cancelButtonText: 'بازگشت به لیست پیش‌فاکتورها',
                reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route("sales.proformas.import.form") }}';
                    } else {
                        window.location.href = '{{ route("sales.proformas.index") }}';
                    }
                });
            } else if (window.confirm(message)) {
                window.location.href = '{{ route("sales.proformas.import.form") }}';
            } else {
                window.location.href = '{{ route("sales.proformas.index") }}';
            }
        });
    </script>
@endif
@endsection
