<script>
function formatPrice(value) {
    value = parseInt(value);
    if (isNaN(value)) return '';
    return value.toLocaleString('fa-IR');
}

function unformatPrice(formatted) {
    return parseInt((formatted || '0').toString().replace(/[٬,]/g, '')) || 0;
}

function toggleDiscount(id) {
    document.getElementById(`discount-fields-${id}`).classList.toggle('hidden');
}

function toggleTax(id) {
    document.getElementById(`tax-fields-${id}`).classList.toggle('hidden');
}

function openProductModal() {
    document.getElementById('productModal').classList.remove('hidden');
    document.getElementById('productModal').classList.add('flex');
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('flex');
    document.getElementById('productModal').classList.add('hidden');
}

//
function handleProductSelection() {
    const checkboxes = document.querySelectorAll('input[name="selected_products[]"]:checked');
    const container = document.getElementById('product-rows-container');

    checkboxes.forEach(checkbox => {
        const productId = checkbox.value;
        const name = checkbox.getAttribute('data-name');
        const price = checkbox.getAttribute('data-price');

        const quantityInput = document.querySelector(`input[name="product_quantities[${productId}]"]`);
        const quantity = quantityInput ? parseFloat(quantityInput.value) : 1;

        if (document.getElementById('product-row-' + productId)) return;

        const row = document.createElement('div');
        row.className = "border p-4 rounded bg-gray-50";
        row.id = 'product-row-' + productId;

        row.innerHTML = `
            <div class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <input type="hidden" name="products[${productId}][id]" value="${productId}">

                    <div>
                        <label class="form-label">نام محصول</label>
                        <input type="text" class="form-control" value="${name}" readonly>
                        <input type="hidden" name="products[${productId}][name]" value="${name}">
                    </div>

                    <div>
                        <label class="form-label">قیمت</label>
                        <input type="text" name="products[${productId}][price]" value="${price}" class="form-control price-field" required>
                    </div>

                    <div>
                        <label class="form-label">تعداد</label>
                        <input type="number" name="products[${productId}][quantity]" class="form-control" value="${quantity}">
                    </div>

                    <div>
                        <label class="form-label">واحد</label>
                        <select name="products[${productId}][unit]" class="form-control">
                            <option value="device">دستگاه</option>
                            <option value="piece">عدد</option>
                            <option value="meter">متر</option>
                        </select>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="button" onclick="removeProductRow('${productId}')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                            حذف
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <div class="flex items-center">
                        <input type="checkbox" id="discount-toggle-${productId}" onchange="toggleDiscount('${productId}')">
                        <label for="discount-toggle-${productId}" class="ml-2">تخفیف</label>
                    </div>

                    <div class="col-span-3 grid grid-cols-2 gap-4 hidden" id="discount-fields-${productId}">
                        <select name="products[${productId}][discount_type]" class="form-control">
                            <option value="percentage">درصدی</option>
                            <option value="fixed">مبلغ ثابت</option>
                        </select>
                        <input type="number" name="products[${productId}][discount_value]" class="form-control" placeholder="مقدار تخفیف">
                    </div>

                    <div class="flex items-center mt-4 md:mt-0">
                        <input type="checkbox" id="tax-toggle-${productId}" onchange="toggleTax('${productId}')">
                        <label for="tax-toggle-${productId}" class="ml-2">مالیات</label>
                    </div>

                    <div class="col-span-3 grid grid-cols-2 gap-4 hidden" id="tax-fields-${productId}">
                        <select name="products[${productId}][tax_type]" class="form-control">
                            <option value="percentage">درصدی</option>
                            <option value="fixed">مبلغ ثابت</option>
                        </select>
                        <input type="number" name="products[${productId}][tax_value]" class="form-control" placeholder="مقدار مالیات">
                    </div>
                </div>
            </div>

            <!-- فیلدهای پنهان برای ثبت کامل -->
            <input type="hidden" name="products[${productId}][subtotal]" class="subtotal-field" value="0">
            <input type="hidden" name="products[${productId}][discount_amount]" class="discount-amount-field" value="0">
            <input type="hidden" name="products[${productId}][tax_amount]" class="tax-amount-field" value="0">
            <input type="hidden" name="products[${productId}][total_after_tax]" class="total-after-tax-field" value="0">
        `;

        container.appendChild(row);

        row.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', calculateInvoiceTotal);
        });
    });

    closeProductModal();
    calculateInvoiceTotal();
}


function removeProductRow(productId) {
    const row = document.getElementById('product-row-' + productId);
    if (row) row.remove();
    calculateInvoiceTotal();
}

function calculateInvoiceTotal() {
    let total = 0;

    document.querySelectorAll('#product-rows-container > div').forEach(row => {
        const idMatch = row.id.match(/product-row-(\d+)/);
        if (!idMatch) return;
        const id = idMatch[1];

        const price = unformatPrice(row.querySelector(`[name="products[${id}][price]"]`)?.value || '0');
        const quantity = parseFloat(row.querySelector(`[name="products[${id}][quantity]"]`)?.value || 0);

        const discountType = row.querySelector(`[name="products[${id}][discount_type]"]`)?.value;
        const discountValue = parseFloat(row.querySelector(`[name="products[${id}][discount_value]"]`)?.value || 0);

        const taxType = row.querySelector(`[name="products[${id}][tax_type]"]`)?.value;
        const taxValue = parseFloat(row.querySelector(`[name="products[${id}][tax_value]"]`)?.value || 0);

        let discount = 0;
        if (discountType === 'percentage') {
            discount = (price * quantity) * (discountValue / 100);
        } else if (discountType === 'fixed') {
            discount = discountValue;
        }

        const subtotal = (price * quantity) - discount;

        let tax = 0;
        if (taxType === 'percentage') {
            tax = subtotal * (taxValue / 100);
        } else if (taxType === 'fixed') {
            tax = taxValue;
        }

        total += subtotal + tax;

        row.querySelector(`.subtotal-field`).value = subtotal;
        row.querySelector(`.discount-amount-field`).value = discount;
        row.querySelector(`.tax-amount-field`).value = tax;
        row.querySelector(`.total-after-tax-field`).value = subtotal + tax;
    });

    document.getElementById('invoice-total').innerText = formatPrice(total);
    document.getElementById('total_amount_input').value = total;
}
</script>
