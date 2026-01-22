@extends('layouts.app')

@section('content')
<h1>فرم ایمپورت</h1>

<div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-xl font-bold mb-4">ایمپورت مخاطبین از فایل Excel</h2>

    {{-- راهنمای فرمت اکسل --}}
    <div class="bg-yellow-50 border border-yellow-300 p-4 rounded mb-6 text-sm text-right leading-relaxed">
        <p class="font-semibold text-yellow-800 mb-2">📋 راهنمای آماده‌سازی فایل اکسل برای ایمپورت مخاطبین:</p>
        <ul class="list-disc list-inside text-gray-800 space-y-1">
            <li><code>first_name</code> – نام مخاطب</li>
            <li><code>last_name</code> – نام خانوادگی مخاطب</li>
            <li><code>email</code> – ایمیل مخاطب</li>
            <li><code>phone</code> – شماره تلفن ثابت</li>
            <li><code>mobile</code> – شماره موبایل (با صفر در ابتدا)</li>
            <li><code>company</code> – نام سازمان مرتبط (در صورت وجود)</li>
            <li><code>city</code> – شهر</li>
            <li><code>assigned_to_email</code> – ایمیل کاربر داخلی که این مخاطب به او ارجاع داده می‌شود</li>
        </ul>
        <p class="mt-2 text-xs text-gray-500">عنوان ستون‌ها باید دقیقاً مطابق بالا، به زبان انگلیسی، بدون فاصله اضافه، و در ردیف اول فایل باشند.</p>
    </div>

    {{-- فرم ایمپورت --}}
    <form method="POST" action="{{ route('sales.contacts.import') }}" enctype="multipart/form-data">
        @csrf
        <label class="block mb-2 font-medium">فایل اکسل را انتخاب کنید:</label>
        <input type="file" name="contacts_file" class="border p-2 w-full mb-4" required accept=".xlsx,.csv">

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
                cancelButtonText: 'بازگشت به لیست مخاطبین',
                reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route("sales.contacts.import") }}';
                    } else {
                        window.location.href = '{{ route("sales.contacts.index") }}';
                    }
                });
            } else if (window.confirm(message)) {
                window.location.href = '{{ route("sales.contacts.import") }}';
            } else {
                window.location.href = '{{ route("sales.contacts.index") }}';
            }
        });
    </script>
@endif
@endsection
