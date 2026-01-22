@extends('layouts.app')
@php
    $breadcrumb = [
        ['title' => 'سازمان‌ها', 'url' => route('sales.organizations.index')],
        ['title' => 'ایمپورت سازمان']
    ];
@endphp
@section('content')
    <div class="max-w-2xl mx-auto mt-10 bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4 text-right">ایمپورت سازمان‌ها از فایل Excel</h1>

        {{-- راهنمای فرمت فایل اکسل --}}
        <div class="bg-yellow-50 border border-yellow-300 p-4 rounded mb-6 text-sm text-right leading-relaxed">
            <p class="font-semibold text-yellow-800 mb-2">📋 راهنمای آماده‌سازی فایل اکسل برای ایمپورت سازمان‌ها:</p>
            <ul class="list-disc list-inside text-gray-800 space-y-1">
                <li><code>name</code> – نام سازمان (الزامی)</li>
                <li><code>phone</code> – شماره تلفن سازمان</li>
                <li><code>industry</code> – زمینه فعالیت یا صنعت سازمان</li>
                <li><code>assigned_to</code> – ایمیل کاربر داخلی سیستم که این سازمان به او ارجاع داده می‌شود</li>
                <li><code>created_at</code> – تاریخ ایجاد سازمان (مثلاً: 2024-01-15)</li>
                <li><code>state</code> – استان محل سازمان</li>
                <li><code>city</code> – شهر محل سازمان</li>
                <li><code>address</code> – آدرس کامل سازمان</li>
                <li><code>description</code> – توضیحات اضافی درباره سازمان</li>
            </ul>
            <p class="mt-2 text-xs text-gray-500">ستون‌ها باید دقیقاً مطابق عنوان‌های بالا، به زبان انگلیسی، بدون فاصله اضافه، و در ردیف اول فایل باشند.</p>
        </div>

        {{-- پیام موفقیت --}}
        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const data = @json(session('success'));

                    if (window.Swal) {
                        Swal.fire({
                        icon: 'success',
                        title: 'عملیات موفق',
                        html: `
                            <p>${data.message}</p>
                            <ul style="text-align: right; margin-top: 10px;">
                                <li>✅ تعداد ردیف‌های ایمپورت شده: <strong>${data.imported}</strong></li>
                                <li>🔁 تعداد ردیف‌های تکراری: <strong>${data.duplicates}</strong></li>
                                <li>❌ تعداد ردیف‌های ناموفق: <strong>${data.failed}</strong></li>
                            </ul>
                            <br>
                            <p>حالا می‌خوای چیکار کنی؟</p>
                        `,
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'بازگشت به سازمان‌ها',
                        denyButtonText: 'ایمپورت جدید',
                        cancelButtonText: 'بستن',
                        reverseButtons: true
                        }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route("sales.organizations.index") }}';
                        } else if (result.isDenied) {
                            window.location.reload();
                        }
                        });
                    } else if (window.confirm(data.message || '')) {
                        window.location.href = '{{ route("sales.organizations.index") }}';
                    } else {
                        window.location.reload();
                    }
                });
            </script>
        @endif




        {{-- پیام خطا --}}
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-800 p-3 rounded mb-4">
                <ul class="list-disc mr-4">
                    @foreach($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- فرم بارگذاری فایل --}}
        <form action="{{ route('sales.organizations.import') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">فایل Excel را انتخاب کنید:</label>
                <input type="file" name="file" id="file" required
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       accept=".xlsx,.csv">
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                شروع ایمپورت
            </button>
        </form>
    </div>
@endsection
