@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">جزئیات تأمین‌کننده</h2>

        <div class="bg-white shadow-md rounded p-6 space-y-4">
            <p><strong>نام:</strong> {{ $supplier->name }}</p>
            <p><strong>ایمیل:</strong> {{ $supplier->email }}</p>
            <p><strong>تلفن:</strong> {{ $supplier->phone }}</p>
            <p><strong>وب‌سایت:</strong> <a href="{{ $supplier->website }}" target="_blank" class="text-blue-500">{{ $supplier->website }}</a></p>
            <p><strong>ارجاع به:</strong> {{ $supplier->user->name ?? '-' }}</p>
            <p><strong>توضیحات:</strong> {{ $supplier->description }}</p>
        </div>

        <div class="mt-6">
            <a href="{{ route('inventory.suppliers.index') }}" class="text-gray-700 underline">بازگشت به لیست</a>
        </div>
    </div>
</div>
@endsection
