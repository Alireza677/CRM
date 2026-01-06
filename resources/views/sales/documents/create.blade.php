@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow p-6 space-y-6">

        {{-- عنوان صفحه --}}
        <h1 class="text-2xl font-bold text-gray-800 border-b pb-4">
            ثبت سند جدید
        </h1>
        
        <form method="POST"
      action="{{ route('sales.documents.store') }}"
      enctype="multipart/form-data"
      class="space-y-5">
    @csrf

    {{-- فرصت یا سفارش خرید --}}
    @if(!empty($defaultOpportunityId))
        {{-- ارسال خودکار فرصت از صفحه خودش --}}
        <input type="hidden" name="opportunity_id" value="{{ $defaultOpportunityId }}">
        <div class="p-3 rounded bg-green-50 text-green-700 text-sm">
            این سند برای «{{ $defaultOpportunityName ?? ('فرصت شماره ' . $defaultOpportunityId) }}» ثبت خواهد شد.
            <a class="underline" href="{{ route('sales.opportunities.show', $defaultOpportunityId) }}">نمایش فرصت</a>
        </div>
    @else
        <div>
            <label class="block font-medium text-sm text-gray-700 mb-1">فرصت فروش</label>
            <select name="opportunity_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">— انتخاب کنید —</option>
                @foreach($opportunities as $op)
                    <option value="{{ $op->id }}"
                        {{ old('opportunity_id') == $op->id ? 'selected' : '' }}>
                        {{ $op->name }}
                    </option>
                @endforeach
            </select>
            @error('opportunity_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    @endif

    @if(!empty($defaultPurchaseOrderId))
        {{-- ارسال خودکار سفارش خرید --}}
        <input type="hidden" name="purchase_order_id" value="{{ $defaultPurchaseOrderId }}">
        <div class="p-3 rounded bg-blue-50 text-blue-700 text-sm">
            این سند برای «{{ $defaultPurchaseOrderSubject ?? ('سفارش خرید شماره ' . $defaultPurchaseOrderId) }}» ثبت خواهد شد.
            <a class="underline" href="{{ route('inventory.purchase-orders.show', $defaultPurchaseOrderId) }}">نمایش سفارش خرید</a>
        </div>
        @error('purchase_order_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    @endif

    {{-- عنوان --}}
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700">عنوان</label>
        <input type="text"
               name="title"
               id="title"
               value="{{ old('title') }}"
               required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
        @error('title')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- فایل --}}
    <div>
        <label for="files" class="block text-sm font-medium text-gray-700">فایل</label>
        <input type="file"
               name="files[]"
               id="files"
               required
               multiple
               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
               class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500">
        @error('files')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        @error('files.*')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        @error('file')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- دکمه ثبت --}}
    <div class="pt-2">
        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow transition">
            ثبت سند
        </button>
    </div>
</form>

    </div>
</div>
@endsection
