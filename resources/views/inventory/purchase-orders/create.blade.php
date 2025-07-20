@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">ایجاد سفارش خرید جدید</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-600">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('inventory.purchase-orders.store') }}" method="POST">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="subject" class="block mb-1">عنوان سفارش:</label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                           class="w-full rounded-md border-gray-300" required>
                </div>

                <div>
                    <label for="supplier_id" class="block mb-1">تأمین‌کننده:</label>
                    <select name="supplier_id" id="supplier_id" class="w-full rounded-md border-gray-300" required>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="purchase_date" class="block mb-1">تاریخ سفارش:</label>
                    <input type="date" name="purchase_date" id="purchase_date"
                           value="{{ old('purchase_date') }}" class="w-full rounded-md border-gray-300" required>
                </div>

                <div>
                    <label for="total_amount" class="block mb-1">مبلغ کل (ریال):</label>
                    <input type="number" name="total_amount" id="total_amount"
                           value="{{ old('total_amount') }}" class="w-full rounded-md border-gray-300" step="0.01">
                </div>

                <div>
                    <label for="assigned_to" class="block mb-1">ارجاع به:</label>
                    <select name="assigned_to" id="assigned_to" class="w-full rounded-md border-gray-300">
                        <option value="">انتخاب کنید</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                     <label for="status" class="block mb-1">وضعیت:</label>
                     <select name="status" id="status" class="w-full rounded-md border-gray-300">
                     <option value="created">ایجاد شده</option>
                     <option value="approved">تأیید شده</option>
                    <option value="delivered">تحویل شده</option>
                    </select>
                </div>


                <div>
                    <label for="description" class="block mb-1">توضیحات:</label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    ذخیره سفارش
                </button>
                <a href="{{ route('inventory.purchase-orders.index') }}" class="ml-4 text-gray-700 underline">بازگشت</a>
            </div>
        </form>
    </div>
</div>
@endsection
