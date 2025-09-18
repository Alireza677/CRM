
<script>
/* ===== Formatting helpers ===== */
function formatPrice(value) {
  value = Math.floor(Number(value));
  if (isNaN(value)) return '';
  return value.toLocaleString('fa-IR');
}
function toLatinDigits(s) {
  return (s || '').toString()
    .replace(/[\u06F0-\u06F9]/g, d => String(d.charCodeAt(0) - 0x06F0))
    .replace(/[\u0660-\u0669]/g, d => String(d.charCodeAt(0) - 0x0660));
}
function unformatPrice(formatted) {
  let s = toLatinDigits(formatted);
  s = s.replace(/[٬, ]/g, '');
  s = s.split(/[.\u066B]/)[0];
  const n = parseInt(s, 10);
  return isNaN(n) ? 0 : n;
}

/* ===== Modal controls ===== */
function openProductModal() {
  document.getElementById('productModal').classList.remove('hidden');
  document.getElementById('productModal').classList.add('flex');
}
function closeProductModal() {
  document.getElementById('productModal').classList.remove('flex');
  document.getElementById('productModal').classList.add('hidden');
}

/* ===== Add/Remove product rows (GLOBAL discount/tax only) ===== */
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
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
          <input type="hidden" name="products[${productId}][id]" value="${productId}">
          <div class="md:col-span-2">
            <label class="form-label">نام محصول</label>
            <input type="text" class="form-control" value="${name}" readonly>
            <input type="hidden" name="products[${productId}][name]" value="${name}">
          </div>
          <div>
            <label class="form-label">قیمت واحد</label>
            <input type="text" name="products[${productId}][price]" value="${price}" class="form-control price-field" required>
          </div>
          <div>
            <label class="form-label">تعداد</label>
            <input type="number" name="products[${productId}][quantity]" class="form-control qty-field" value="${quantity}" min="0" step="1">
          </div>
          <div>
            <label class="form-label">واحد</label>
            <select name="products[${productId}][unit]" class="form-control">
              <option value="device">دستگاه</option>
              <option value="piece">عدد</option>
              <option value="meter">متر</option>
            </select>
          </div>
          <div class="flex items-end justify-between">
            <div class="text-sm text-gray-600">
              مبلغ ردیف:
              <span class="line-total font-semibold" data-item-total="0">۰</span>
            </div>
            <button type="button" onclick="removeProductRow('${productId}')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
              حذف
            </button>
          </div>
        </div>
      </div>
    `;

    container.appendChild(row);

    // Normalize & format initial price
    const priceInput = row.querySelector(`input[name="products[${productId}][price]"]`);
    const intPrice = unformatPrice(price);
    priceInput.value = formatPrice(intPrice);

    // Recalc on inputs
    row.querySelectorAll('input, select').forEach(el => {
      el.addEventListener('input', calculateInvoiceTotal);
      el.addEventListener('change', calculateInvoiceTotal);
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

/* ===== Global totals (no per-item discount/tax) ===== */
function calculateInvoiceTotal() {
  let itemsSubtotal = 0;

  document.querySelectorAll('#product-rows-container > div[id^="product-row-"]').forEach(row => {
    const idMatch = row.id.match(/product-row-(\d+)/);
    if (!idMatch) return;
    const id = idMatch[1];

    const price = unformatPrice(row.querySelector(`[name="products[${id}][price]"]`)?.value || '0');
    const quantity = parseFloat(toLatinDigits(row.querySelector(`[name="products[${id}][quantity]"]`)?.value || '0')) || 0;

    const lineTotal = Math.max(Math.floor(price * quantity), 0);
    itemsSubtotal += lineTotal;

    const lineTotalEl = row.querySelector('.line-total');
    if (lineTotalEl) {
      lineTotalEl.textContent = formatPrice(lineTotal);
      lineTotalEl.setAttribute('data-item-total', lineTotal.toString());
    }
  });

  // Read global discount/tax controls
  const dt = (document.querySelector('[name="global_discount_type"]')?.value || '').trim();
  const dv = unformatPrice(document.querySelector('[name="global_discount_value"]')?.value || '0');
  const tt = (document.querySelector('[name="global_tax_type"]')?.value || '').trim();
  const tv = unformatPrice(document.querySelector('[name="global_tax_value"]')?.value || '0');

  // Compute global discount
  let discountAmount = 0;
  if (dt === 'percentage') discountAmount = Math.floor(itemsSubtotal * (dv / 100));
  if (dt === 'fixed')      discountAmount = Math.min(dv, itemsSubtotal);

  const taxBase = Math.max(itemsSubtotal - discountAmount, 0);

  // Compute global tax
  let taxAmount = 0;
  if (tt === 'percentage') taxAmount = Math.floor(taxBase * (tv / 100));
  if (tt === 'fixed')      taxAmount = tv;

  const total = Math.max(taxBase + taxAmount, 0);

  // Update summary UI (make sure these elements exist in Blade)
  const elSubtotal = document.getElementById('items-subtotal');
  const elGDisc    = document.getElementById('global-discount-amount');
  const elGTax     = document.getElementById('global-tax-amount');
  const elTotal    = document.getElementById('invoice-total');

  if (elSubtotal) elSubtotal.textContent = formatPrice(itemsSubtotal);
  if (elGDisc)    elGDisc.textContent    = formatPrice(discountAmount);
  if (elGTax)     elGTax.textContent     = formatPrice(taxAmount);
  if (elTotal)    elTotal.textContent    = formatPrice(total);

  // Hidden input for server
  const totalInput = document.getElementById('total_amount_input');
  if (totalInput) totalInput.value = total;

  // Optional hidden fields if you want to post them (uncomment if present in form)
  // setHiddenVal('items_subtotal_input', itemsSubtotal);
  // setHiddenVal('global_discount_amount_input', discountAmount);
  // setHiddenVal('global_tax_amount_input', taxAmount);
}
function setHiddenVal(id, val){
  const el = document.getElementById(id);
  if (el) el.value = val;
}

/* ===== Events ===== */
document.addEventListener('input', function(e) {
  // Live formatting for price fields
  if (e.target.classList.contains('price-field')) {
    let val = unformatPrice(e.target.value);
    e.target.value = formatPrice(val);
    calculateInvoiceTotal();
  }
  if (e.target.classList.contains('qty-field')) {
    // force integers for qty if needed
    const raw = toLatinDigits(e.target.value);
    const n = Math.max(parseInt(raw || '0', 10) || 0, 0);
    e.target.value = n;
    calculateInvoiceTotal();
  }
});

// Recalculate when global controls change
['change','keyup'].forEach(ev => {
  document.addEventListener(ev, (e) => {
    if (e.target.matches('[name="global_discount_type"],[name="global_discount_value"],[name="global_tax_type"],[name="global_tax_value"]')) {
      calculateInvoiceTotal();
    }
  });
});

// Initial calc after DOM ready
window.addEventListener('DOMContentLoaded', calculateInvoiceTotal);
</script>

