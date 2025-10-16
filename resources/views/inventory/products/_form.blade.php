@php
    /** @var \App\Models\Product|null $product */
    $isEdit = isset($product) && $product && $product->exists;
    $action = $isEdit
        ? route('inventory.products.update', $product)
        : route('inventory.products.store');

    $val = fn($key, $fallback = null) => old($key, $fallback);

    $dateVal = function ($key, $modelDate = null) {
        $old = old($key);
        if (!is_null($old)) return $old;
        if ($modelDate instanceof \Carbon\CarbonInterface) return $modelDate->format('Y-m-d');
        // اگر در مدل cast نشده بود و رشته بود:
        if (is_string($modelDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $modelDate)) return $modelDate;
        try { return $modelDate ? \Carbon\Carbon::parse($modelDate)->format('Y-m-d') : null; } catch (\Throwable $e) { return null; }
    };
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <form action="{{ $action }}" method="POST" class="p-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- ستون راست --}}
            <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات محصول</h3>

                    <div class="space-y-4">
                        {{-- نام محصول --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                نام محصول <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('name', $product->name ?? '') }}">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- قیمت واحد --}}
                        <div>
                            <label for="unit_price" class="block text-sm font-medium text-gray-700">
                                قیمت واحد <span class="text-red-500">*</span>
                            </label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input type="number" step="0.01" name="unit_price" id="unit_price" required
                                       class="block w-full rounded-md border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500"
                                       value="{{ $val('unit_price', $product->unit_price ?? '') }}">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">ریال</span>
                                </div>
                            </div>
                            @error('unit_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- چک‌باکس‌ها --}}
                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="has_vat" id="has_vat" value="1"
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       {{ $val('has_vat', ($product->has_vat ?? false)) ? 'checked' : '' }}>
                                <span class="mr-2 text-sm text-gray-900">مشمول مالیات</span>
                            </label>

                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1"
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       {{ $val('is_active', ($product->is_active ?? true)) ? 'checked' : '' }}>
                                <span class="mr-2 text-sm text-gray-900">فعال</span>
                            </label>
                        </div>

                        {{-- تاریخ‌ها --}}
                        <div>
                            <label for="sales_start_date" class="block text-sm font-medium text-gray-700">تاریخ شروع فروش</label>
                            <input type="date" name="sales_start_date" id="sales_start_date"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $dateVal('sales_start_date', $product->sales_start_date ?? null) }}">
                            @error('sales_start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="support_start_date" class="block text-sm font-medium text-gray-700">تاریخ شروع پشتیبانی</label>
                            <input type="date" name="support_start_date" id="support_start_date"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $dateVal('support_start_date', $product->support_start_date ?? null) }}">
                            @error('support_start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- دسته‌بندی --}}
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">طبقه‌بندی محصول</label>
                            <select name="category_id" id="category_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">یک گزینه را انتخاب کنید</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ (string)$val('category_id', $product->category_id ?? '') === (string)$category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- تامین‌کننده --}}
                        <div>
                            <label for="supplier_id" class="block text-sm font-medium text-gray-700">نام تامین‌کننده</label>
                            <select name="supplier_id" id="supplier_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">یک گزینه را انتخاب کنید</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ (string)$val('supplier_id', $product->supplier_id ?? '') === (string)$supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- تولیدکننده/سری/طول --}}
                        <div>
                            <label for="manufacturer" class="block text-sm font-medium text-gray-700">تولیدکننده</label>
                            <input type="text" name="manufacturer" id="manufacturer"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('manufacturer', $product->manufacturer ?? '') }}">
                        </div>

                        <div>
                            <label for="series" class="block text-sm font-medium text-gray-700">سری</label>
                            <input type="text" name="series" id="series"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('series', $product->series ?? '') }}">
                        </div>

                        <div>
                            <label for="length" class="block text-sm font-medium text-gray-700">طول (متر)</label>
                            <input type="number" step="0.01" name="length" id="length"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('length', $product->length ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ستون چپ --}}
            <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="space-y-4">
                        <div>
                            <label for="sales_end_date" class="block text-sm font-medium text-gray-700">تاریخ اتمام فروش</label>
                            <input type="date" name="sales_end_date" id="sales_end_date"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $dateVal('sales_end_date', $product->sales_end_date ?? null) }}">
                        </div>

                        <div>
                            <label for="support_end_date" class="block text-sm font-medium text-gray-700">تاریخ اتمام پشتیبانی</label>
                            <input type="date" name="support_end_date" id="support_end_date"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $dateVal('support_end_date', $product->support_end_date ?? null) }}">
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700">وب‌سایت</label>
                            <input type="url" name="website" id="website"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('website', $product->website ?? '') }}">
                        </div>

                        <div>
                            <label for="part_number" class="block text-sm font-medium text-gray-700">شماره قطعه</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input type="text" name="part_number" id="part_number"
                                       class="block w-full rounded-md border-gray-300 pr-10 focus:border-blue-500 focus:ring-blue-500"
                                       value="{{ $val('part_number', $product->part_number ?? '') }}">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <button type="button" class="text-gray-400 hover:text-gray-500" title="جستجو">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">نوع</label>
                            <select name="type" id="type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">یک گزینه را انتخاب کنید</option>
                                <option value="standard" {{ $val('type', $product->type ?? '') === 'standard' ? 'selected' : '' }}>استاندارد</option>
                                <option value="custom"   {{ $val('type', $product->type ?? '') === 'custom'   ? 'selected' : '' }}>سفارشی</option>
                            </select>
                        </div>

                        <div>
                            <label for="thermal_power" class="block text-sm font-medium text-gray-700">توان حرارتی (kW)</label>
                            <input type="number" step="0.01" name="thermal_power" id="thermal_power"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('thermal_power', $product->thermal_power ?? '') }}">
                        </div>

                        <div>
                            <label for="commission" class="block text-sm font-medium text-gray-700">پورسانت (%)</label>
                            <input type="number" step="0.01" name="commission" id="commission"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $val('commission', $product->commission ?? '') }}">
                        </div>

                        <div>
                            <label for="purchase_cost" class="block text-sm font-medium text-gray-700">هزینه خرید</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input type="number" step="0.01" name="purchase_cost" id="purchase_cost"
                                       class="block w-full rounded-md border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500"
                                       value="{{ $val('purchase_cost', $product->purchase_cost ?? '') }}">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">ریال</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- فوتر ثابت --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
            <div class="max-w-7xl mx-auto flex justify-end gap-3">
                <a href="{{ route('inventory.products.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    لغو
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                    {{ $isEdit ? 'به‌روزرسانی' : 'ذخیره' }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $.fn.select2) {
            $('#category_id, #supplier_id').select2({ placeholder: 'یک گزینه را انتخاب کنید', allowClear: true, dir: 'rtl' });
        }
    });
</script>
@endpush
