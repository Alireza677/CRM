<!-- Modal: انتخاب محصول -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6">
        <h3 class="text-lg font-semibold mb-4">انتخاب محصول</h3>

        {{-- نوار جستجو --}}
        <div class="mb-3">
            <input
                id="productSearchInput"
                type="text"
                placeholder="جستجوی نام یا قیمت…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                autocomplete="off"
            />
            <div class="mt-1 text-xs text-gray-500">
                تایپ کنید تا لیست فیلتر شود. (نام محصول یا قیمت)
            </div>
        </div>

        <div id="productSelectionWrapper">
            <div class="overflow-y-auto max-h-64 border rounded">
                <table class="min-w-full text-sm text-right">
                    <thead class="bg-gray-100 text-gray-700 sticky top-0">
                        <tr>
                            <th class="px-4 py-2">انتخاب</th>
                            <th class="px-4 py-2">نام محصول</th>
                            <th class="px-4 py-2">قیمت (ریال)</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        @foreach ($products as $product)
                            <tr
                                class="border-t"
                                data-name="{{ Str::lower($product->name) }}"
                                data-price="{{ (int) $product->unit_price }}"
                            >
                                <td class="px-4 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        name="selected_products[]"
                                        value="{{ $product->id }}"
                                        data-name="{{ $product->name }}"
                                        data-price="{{ $product->unit_price }}"
                                        data-id="{{ $product->id }}"
                                    >
                                    <input
                                        type="number"
                                        name="product_quantities[{{ $product->id }}]"
                                        value="1"
                                        min="1"
                                        class="form-control mt-1 text-sm modal-quantity-input border rounded px-2 py-1"
                                        style="width: 60px;"
                                    >
                                </td>
                                <td class="px-4 py-2">{{ $product->name }}</td>
                                <td class="px-4 py-2">{{ number_format($product->unit_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- وضعیت خالی --}}
                <div id="noResultsRow" class="hidden p-4 text-center text-sm text-gray-500">
                    موردی یافت نشد.
                </div>
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


<script>
(function () {
    const $input = document.getElementById('productSearchInput');
    const $tbody = document.getElementById('productTableBody');
    const $noResults = document.getElementById('noResultsRow');

    if (!$input || !$tbody) return;

    // تبدیل اعداد فارسی/عربی به انگلیسی برای جستجو روی قیمت
    function normalizeDigits(str) {
        if (!str) return '';
        const fa = '۰۱۲۳۴۵۶۷۸۹';
        const ar = '٠١٢٣٤٥٦٧٨٩';
        return String(str).split('').map(ch => {
            const iFa = fa.indexOf(ch);
            if (iFa > -1) return String(iFa);
            const iAr = ar.indexOf(ch);
            if (iAr > -1) return String(iAr);
            return ch;
        }).join('');
    }

    // Debounce ساده برای بهینه‌سازی
    let t = null;
    $input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(applyFilter, 150);
    });

    function applyFilter() {
        const qRaw = $input.value || '';
        const q = normalizeDigits(qRaw.trim().toLowerCase());

        const rows = Array.from($tbody.querySelectorAll('tr'));
        let visibleCount = 0;

        if (!q) {
            // نمایش همه وقتی جستجو خالی است
            rows.forEach(tr => tr.classList.remove('hidden'));
            $noResults.classList.add('hidden');
            return;
        }

        rows.forEach(tr => {
            const name = (tr.getAttribute('data-name') || '').toLowerCase();
            const price = String(tr.getAttribute('data-price') || '');
            const match = name.includes(q) || price.includes(q.replace(/,/g, '')); // جستجو در نام یا قیمت
            if (match) {
                tr.classList.remove('hidden');
                visibleCount++;
            } else {
                tr.classList.add('hidden');
            }
        });

        if (visibleCount === 0) {
            $noResults.classList.remove('hidden');
        } else {
            $noResults.classList.add('hidden');
        }
    }
})();
</script>
