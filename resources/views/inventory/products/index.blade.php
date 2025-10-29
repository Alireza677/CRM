@extends('layouts.app')

@php
    $breadcrumb = [['title' => 'محصولات']];
@endphp

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">محصولات</h2>

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
        <form action="{{ route('inventory.products.index') }}" method="GET" class="w-full sm:w-1/3">
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full pr-10 pl-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="جستجو...">
                <button type="submit" class="absolute left-0 inset-y-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0z" />
                    </svg>
                </button>
            </div>
        </form>
        <form action="{{ route('inventory.products.index') }}" method="GET" class="w-auto">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="direction" value="{{ request('direction') }}">
            <label for="per_page" class="text-sm text-gray-700 mr-2">تعداد در صفحه</label>
            @php $pp = (int) request('per_page', 10); @endphp
            <select id="per_page" name="per_page" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $pp === 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $pp === 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ $pp === 200 ? 'selected' : '' }}>200</option>
            </select>
        </form>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventory.products.import') }}"
               class="inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                درون‌ریزی محصولات
            </a>
            <a href="{{ route('inventory.products.create') }}"
               class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                افزودن محصول جدید
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-gray-600">
                        <th class="px-4 py-3 text-right font-medium">نام محصول</th>
                        <th class="px-4 py-3 text-right font-medium">کد محصول</th>
                        <th class="px-4 py-3 text-right font-medium">سریال</th>
                        <th class="px-4 py-3 text-right font-medium">موجودی</th>
                        <th class="px-4 py-3 text-right font-medium">قیمت واحد</th>
                        <th class="px-4 py-3 text-right font-medium">تحویل‌گیرنده</th>
                        <th class="px-4 py-3 text-right font-medium">درصد</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium">دسته‌بندی</th>
                        <th class="px-4 py-3 text-right font-medium">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('inventory.products.show', $product) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $product->code }}</td>
                            <td class="px-4 py-3">{{ $product->serial_number }}</td>
                            <td class="px-4 py-3">{{ $product->stock_quantity }}</td>
                            <td class="px-4 py-3">{{ number_format($product->unit_price) }}</td>
                            <td class="px-4 py-3">{{ $product->receiver_name }}</td>
                            <td class="px-4 py-3">
                                {{ $product->percentage ? $product->percentage . '%' : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $product->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $product->category_name }}</td>
                            <td class="px-4 py-3 text-sm font-medium">
                                <div class="flex gap-4">
                                    <a href="{{ route('inventory.products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                    @role('admin')
                                    <form action="{{ route('inventory.products.destroy', $product) }}" method="POST" onsubmit="return confirm('از حذف این محصول مطمئن هستید؟ این عملیات قابل بازگشت نیست.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                    </form>
                                    @endrole
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3">
        
        <div>
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
