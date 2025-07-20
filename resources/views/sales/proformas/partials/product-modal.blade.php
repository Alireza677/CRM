<!-- Modal: انتخاب محصول -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6">
        <h3 class="text-lg font-semibold mb-4">انتخاب محصول</h3>
        <div id="productSelectionWrapper">
            <div class="overflow-y-auto max-h-64 border rounded">
            <table class="min-w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-2">انتخاب</th>
                        <th class="px-4 py-2">نام محصول</th>
                        <th class="px-4 py-2">قیمت (تومان)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-t">
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="selected_products[]" value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ $product->unit_price }}"
                                    data-id="{{ $product->id }}">

                                {{-- فیلد تعداد برای هر محصول --}}
                                <input type="number" name="product_quantities[{{ $product->id }}]"
                                    value="1" min="1" class="form-control mt-1 text-sm modal-quantity-input" style="width: 60px;">
                            </td>
                            <td class="px-4 py-2">{{ $product->name }}</td>
                            <td class="px-4 py-2">{{ number_format($product->unit_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            </div>

            <div class="flex justify-between mt-4">
                <button type="button" onclick="handleProductSelection()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    افزودن
                </button>
                <button type="button" onclick="closeProductModal()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400 text-sm">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>
