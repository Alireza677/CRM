@extends('layouts.app')

@section('content')
<h1>فرم ایمپورت</h1>

<div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-xl font-bold mb-4">Import Contacts from Excel</h2>

    <form method="POST" action="{{ route('sales.contacts.import') }}" enctype="multipart/form-data">
        @csrf
        <label class="block mb-2 font-medium">Choose Excel file:</label>
        <input type="file" name="contacts_file" class="border p-2 w-full mb-4" required>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Import Contacts
        </button>
    </form>
</div>

@if(session('success'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
@endif
@endsection
