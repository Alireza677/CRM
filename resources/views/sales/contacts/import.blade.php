@extends('layouts.app')

@section('content')
<h1>فرم ایمپورت</h1>

<div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-xl font-bold mb-4">Import Contacts from Excel</h2>
    
    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('sales.contacts.import') }}" enctype="multipart/form-data">
        @csrf
        <label class="block mb-2 font-medium">Choose Excel file:</label>
        <input type="file" name="contacts_file" class="border p-2 w-full mb-4" required>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Import Contacts
        </button>
    </form>
</div>
@endsection
