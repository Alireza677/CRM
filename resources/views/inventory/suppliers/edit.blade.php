@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">ویرایش تأمین‌کننده</h2>

        <form method="POST" action="{{ route('inventory.suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4">
                <label>نام:</label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required class="rounded-md border-gray-300">

                <label>ایمیل:</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="rounded-md border-gray-300">

                <label>تلفن:</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="rounded-md border-gray-300">

                <label>وب‌سایت:</label>
                <input type="url" name="website" value="{{ old('website', $supplier->website) }}" class="rounded-md border-gray-300">

                <label>ارجاع به:</label>
                <select name="assigned_to" class="rounded-md border-gray-300">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('assigned_to', $supplier->assigned_to) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">ذخیره تغییرات</button>
                <a href="{{ route('inventory.suppliers.index') }}" class="text-gray-700 underline ml-4">بازگشت</a>
            </div>
        </form>
    </div>
</div>
@endsection
