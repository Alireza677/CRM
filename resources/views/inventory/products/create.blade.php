<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('افزودن محصول جدید') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('inventory.products.store') }}" method="POST" class="p-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Right Column -->
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات محصول</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">
                                            نام محصول <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="name" id="name" required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('name') }}">
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="sales_start_date" class="block text-sm font-medium text-gray-700">
                                            تاریخ آغاز فروش
                                        </label>
                                        <input type="date" name="sales_start_date" id="sales_start_date"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('sales_start_date') }}">
                                    </div>

                                    <div>
                                        <label for="support_start_date" class="block text-sm font-medium text-gray-700">
                                            تاریخ شروع پشتیبانی
                                        </label>
                                        <input type="date" name="support_start_date" id="support_start_date"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('support_start_date') }}">
                                    </div>

                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-gray-700">
                                            طبقه بندی محصول
                                        </label>
                                        <select name="category_id" id="category_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">یک گزینه را انتخاب کنید</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="supplier_id" class="block text-sm font-medium text-gray-700">
                                            نام تامین کننده
                                        </label>
                                        <select name="supplier_id" id="supplier_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">یک گزینه را انتخاب کنید</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="manufacturer" class="block text-sm font-medium text-gray-700">
                                            تولید کننده
                                        </label>
                                        <input type="text" name="manufacturer" id="manufacturer"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('manufacturer') }}">
                                    </div>

                                    <div>
                                        <label for="series" class="block text-sm font-medium text-gray-700">
                                            سری
                                        </label>
                                        <input type="text" name="series" id="series"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('series') }}">
                                    </div>

                                    <div>
                                        <label for="length" class="block text-sm font-medium text-gray-700">
                                            طول متر
                                        </label>
                                        <input type="number" name="length" id="length" step="0.01"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('length') }}">
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">نحوه قیمت‌گذاری</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label for="unit_price" class="block text-sm font-medium text-gray-700">
                                                    قیمت واحد
                                                </label>
                                                <div class="relative mt-1 rounded-md shadow-sm">
                                                    <input type="number" name="unit_price" id="unit_price" step="0.01"
                                                           class="block w-full rounded-md border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500"
                                                           value="{{ old('unit_price') }}">
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                        <span class="text-gray-500 sm:text-sm">ریال</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center">
                                                <input type="checkbox" name="has_vat" id="has_vat"
                                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                       {{ old('has_vat') ? 'checked' : '' }}>
                                                <label for="has_vat" class="mr-2 block text-sm text-gray-700">
                                                    ارزش افزوده (%)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Left Column -->
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات موجودی انبار</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_active" id="is_active"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label for="is_active" class="mr-2 block text-sm text-gray-700">
                                            محصول فعال است
                                        </label>
                                    </div>

                                    <div>
                                        <label for="sales_end_date" class="block text-sm font-medium text-gray-700">
                                            تاریخ اتمام فروش
                                        </label>
                                        <input type="date" name="sales_end_date" id="sales_end_date"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('sales_end_date') }}">
                                    </div>

                                    <div>
                                        <label for="support_end_date" class="block text-sm font-medium text-gray-700">
                                            تاریخ اتمام پشتیبانی
                                        </label>
                                        <input type="date" name="support_end_date" id="support_end_date"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('support_end_date') }}">
                                    </div>

                                    <div>
                                        <label for="website" class="block text-sm font-medium text-gray-700">
                                            وب سایت
                                        </label>
                                        <input type="url" name="website" id="website"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('website') }}">
                                    </div>

                                    <div>
                                        <label for="part_number" class="block text-sm font-medium text-gray-700">
                                            شماره قطعه
                                        </label>
                                        <div class="relative mt-1 rounded-md shadow-sm">
                                            <input type="text" name="part_number" id="part_number"
                                                   class="block w-full rounded-md border-gray-300 pr-10 focus:border-blue-500 focus:ring-blue-500"
                                                   value="{{ old('part_number') }}">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <button type="button" class="text-gray-400 hover:text-gray-500">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="type" class="block text-sm font-medium text-gray-700">
                                            نوع
                                        </label>
                                        <select name="type" id="type"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">یک گزینه را انتخاب کنید</option>
                                            <option value="standard" {{ old('type') == 'standard' ? 'selected' : '' }}>استاندارد</option>
                                            <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>سفارشی</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="thermal_power" class="block text-sm font-medium text-gray-700">
                                            توان حرارتی kw
                                        </label>
                                        <input type="number" name="thermal_power" id="thermal_power" step="0.01"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('thermal_power') }}">
                                    </div>

                                    <div>
                                        <label for="commission" class="block text-sm font-medium text-gray-700">
                                            پورسانت (%)
                                        </label>
                                        <input type="number" name="commission" id="commission" step="0.01"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               value="{{ old('commission') }}">
                                    </div>

                                    <div>
                                        <label for="purchase_cost" class="block text-sm font-medium text-gray-700">
                                            هزینه خرید
                                        </label>
                                        <div class="relative mt-1 rounded-md shadow-sm">
                                            <input type="number" name="purchase_cost" id="purchase_cost" step="0.01"
                                                   class="block w-full rounded-md border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500"
                                                   value="{{ old('purchase_cost') }}">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <span class="text-gray-500 sm:text-sm">ریال</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Footer -->
                    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
                        <div class="max-w-7xl mx-auto flex justify-end space-x-3">
                            <a href="{{ route('inventory.products.index') }}"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                لغو
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                ذخیره
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Initialize select2 for searchable selects
        $(document).ready(function() {
            $('#category_id, #supplier_id').select2({
                placeholder: 'یک گزینه را انتخاب کنید',
                allowClear: true,
                dir: 'rtl'
            });
        });
    </script>
    @endpush
</x-app-layout> 