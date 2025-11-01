@extends('layouts.app')

@section('content')
<div class="py-12">
  <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
    <h2 class="text-2xl font-bold mb-6">ایجاد سفارش خرید</h2>

    @if ($errors->any())
      <div class="mb-4 text-red-600">
        <ul class="list-disc pr-5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('inventory.purchase-orders.store') }}" method="POST" id="po-form" enctype="multipart/form-data">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-6 rounded shadow">
        <div>
          <label for="subject" class="block text-sm font-medium mb-1">عنوان</label>
          <input type="text" name="subject" id="subject" value="{{ old('subject') }}" class="w-full rounded-md border-gray-300" required>
        </div>

        <div>
          <label for="purchase_type" class="block text-sm font-medium mb-1">نوع خرید</label>
          <select name="purchase_type" id="purchase_type" class="w-full rounded-md border-gray-300" required>
            <option value="official" {{ old('purchase_type')==='official' ? 'selected' : '' }}>رسمی</option>
            <option value="unofficial" {{ old('purchase_type')==='unofficial' ? 'selected' : '' }}>غیررسمی</option>
          </select>
        </div>

        <div>
          <label for="settlement_type" class="block text-sm font-medium mb-1">نوع تسویه حساب</label>
          <select name="settlement_type" id="settlement_type" class="w-full rounded-md border-gray-300">
            <option value="">انتخاب کنید</option>
            <option value="cash"   {{ old('settlement_type')==='cash' ? 'selected' : '' }}>نقد</option>
            <option value="credit" {{ old('settlement_type')==='credit' ? 'selected' : '' }}>نسیه</option>
            <option value="cheque" {{ old('settlement_type')==='cheque' ? 'selected' : '' }}>چک</option>
          </select>
        </div>

        <div>
          <label for="usage_type" class="block text-sm font-medium mb-1">مورد استفاده</label>
          <select name="usage_type" id="usage_type" class="w-full rounded-md border-gray-300">
            <option value="">انتخاب کنید</option>
            <option value="inventory" {{ old('usage_type')==='inventory' ? 'selected' : '' }}>تکمیل موجودی انبار</option>
            <option value="project"   {{ old('usage_type')==='project' ? 'selected' : '' }}>تکمیل پروژه</option>
            <option value="both"      {{ old('usage_type')==='both' ? 'selected' : '' }}>هر دو</option>
          </select>
        </div>

        <div id="project_name_wrapper" class="md:col-span-2" style="display:none;">
          <label for="project_name" class="block text-sm font-medium mb-1">نام پروژه</label>
          <input type="text" name="project_name" id="project_name" value="{{ old('project_name') }}" class="w-full rounded-md border-gray-300" placeholder="مثلاً: پروژه ساختمان الف">
        </div>

        <div>
          <label for="supplier_display" class="block text-sm font-medium mb-1">تأمین‌کننده</label>
          <div class="flex gap-2">
            <input type="text" id="supplier_display" class="flex-1 rounded-md border-gray-300 bg-gray-100" placeholder="انتخاب تأمین‌کننده" readonly required>
            <button type="button" id="btn_open_supplier" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">انتخاب</button>
          </div>
          <input type="hidden" name="supplier_id" id="supplier_id" value="{{ old('supplier_id') }}">
        </div>

        <div>
          <label for="requested_by" class="block text-sm font-medium mb-1">درخواست‌کننده</label>
          <select name="requested_by" id="requested_by" class="w-full rounded-md border-gray-300">
            <option value="">انتخاب کنید</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}" {{ old('requested_by', auth()->id()) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="request_date_shamsi" class="block text-sm font-medium mb-1">تاریخ درخواست</label>
          <input type="text" id="request_date_shamsi" class="persian-datepicker w-full rounded-md border-gray-300" data-alt-field="request_date" placeholder="YYYY/MM/DD">
          <input type="hidden" name="request_date" id="request_date" value="{{ old('request_date') }}">
        </div>

        <div>
          <label for="purchase_date_shamsi" class="block text-sm font-medium mb-1">تاریخ خرید</label>
          <input type="text" id="purchase_date_shamsi" class="persian-datepicker w-full rounded-md border-gray-300" data-alt-field="purchase_date" placeholder="YYYY/MM/DD" required>
          <input type="hidden" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}">
        </div>

        <div>
          <label for="needed_by_date_shamsi" class="block text-sm font-medium mb-1">نیاز تا تاریخ</label>
          <input type="text" id="needed_by_date_shamsi" class="persian-datepicker w-full rounded-md border-gray-300" data-alt-field="needed_by_date" placeholder="YYYY/MM/DD">
          <input type="hidden" name="needed_by_date" id="needed_by_date" value="{{ old('needed_by_date') }}">
        </div>

        {{-- وضعیت پیش‌فرض: ایجاد شده (بدون نیاز به انتخاب کاربر) --}}
        <input type="hidden" name="status" value="created">

        <div class="md:col-span-2">
          <label for="description" class="block text-sm font-medium mb-1">توضیحات و مشخصات حساب بانکی</label>
          <textarea name="description" id="description" rows="3" class="w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
        </div>
      </div>

      <div class="mt-8 bg-white p-6 rounded shadow">
  <div class="mb-4">
    <label class="block text-sm font-medium mb-1">افزودن تصویر یا سند</label>
    <input type="file" name="attachments[]" accept="image/*" multiple class="block w-full text-sm text-gray-700" />
    <p class="text-xs text-gray-500 mt-1">فرمت‌های مجاز: JPG, PNG — حداکثر ۱۰ مگابایت برای هر فایل</p>
  </div>

  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold">آیتم‌ها</h3>
    <button type="button" id="add-item" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">افزودن آیتم</button>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm" id="items-table">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-right">نام کالا / خدمت</th>
          <th class="px-3 py-2 text-right">تعداد</th>
          <th class="px-3 py-2 text-right">واحد</th>
          <th class="px-3 py-2 text-right">قیمت واحد</th>
          <th class="px-3 py-2 text-right">مبلغ ردیف</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody id="items-body">
        <tr id="item-row-template" class="hidden">
          <td class="px-3 py-2">
            <input data-name="items[__INDEX__][item_name]" class="w-48 rounded-md border-gray-300 item-name" disabled required>
          </td>
          <td class="px-3 py-2">
            <input type="number" step="0.001" min="0.001" data-name="items[__INDEX__][quantity]" class="w-24 rounded-md border-gray-300 item-qty" disabled required>
          </td>
          <td class="px-3 py-2">
            <select data-name="items[__INDEX__][unit]" class="w-28 rounded-md border-gray-300 item-unit" disabled required>
              <option value="">—</option>
              <option value="عدد">عدد</option>
              <option value="متر">متر</option>
              <option value="کیلوگرم">کیلوگرم</option>
              <option value="مترمربع">متر مربع</option>
              <option value="دستگاه">دستگاه</option>
            </select>
          </td>
          <td class="px-3 py-2">
            <input type="number" step="0.01" min="0" data-name="items[__INDEX__][unit_price]" class="w-28 rounded-md border-gray-300 item-price" disabled required>
          </td>
          <td class="px-3 py-2"><span class="item-total">۰</span></td>
          <td class="px-3 py-2 text-right">
            <button type="button" class="text-red-600 remove-row" disabled>حذف</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

        <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
          <div>
            <label class="block text-sm font-medium mb-1">جمع کل</label>
            <input type="text" id="total_amount_display" class="w-full rounded-md border-gray-300 bg-gray-100" readonly>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 bg-white p-6 rounded shadow">
        <div class="flex items-center mt-2">
          <input type="checkbox" id="apply_vat" class="mr-2">
          <label for="apply_vat" class="text-sm">اعمال ارزش افزوده</label>
        </div>
        <div>
          <label for="vat_percent" class="block text-sm font-medium mb-1">درصد ارزش افزوده (%)</label>
          <input type="number" step="0.01" min="0" max="100" name="vat_percent" id="vat_percent" value="{{ old('vat_percent') }}" class="w-full rounded-md border-gray-300" disabled>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">مبلغ ارزش افزوده</label>
          <input type="text" id="vat_amount_display" class="w-full rounded-md border-gray-300 bg-gray-100" readonly>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">جمع با ارزش افزوده</label>
          <input type="text" id="grand_total_display" class="w-full rounded-md border-gray-300 bg-gray-100" readonly>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label for="previously_paid_amount" class="block text-sm font-medium mb-1">مبلغ‌های پرداخت‌شده قبلی</label>
          <input type="number" step="1" min="0" name="previously_paid_amount" id="previously_paid_amount" value="{{ old('previously_paid_amount', 0) }}" class="w-full rounded-md border-gray-300">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">مانده قابل پرداخت</label>
          <input type="text" id="remaining_display" class="w-full rounded-md border-gray-300 bg-gray-100" readonly>
        </div>
      </div>


      <div class="mt-6 flex items-center">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">ثبت سفارش خرید</button>
        <a href="{{ route('inventory.purchase-orders.index') }}" class="mr-4 text-gray-700 underline">بازگشت</a>
      </div>
    </form>
  </div>
</div>

<!-- Supplier Modal -->
<div id="supplier_modal" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-black/50" onclick="closeSupplierModal()"></div>
  <div class="relative bg-white w-full max-w-2xl mx-auto mt-24 rounded shadow-lg">
    <div class="p-4 border-b flex items-center justify-between">
      <h3 class="text-lg font-semibold">انتخاب تأمین‌کننده</h3>
      <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeSupplierModal()">✕</button>
    </div>
    <div class="p-4">
      <input type="text" id="supplier_search" oninput="filterSuppliers()" class="w-full rounded-md border-gray-300 mb-4" placeholder="جستجو...">
      <div class="max-h-80 overflow-auto divide-y">
        @foreach($suppliers as $s)
          <div class="supplier-row cursor-pointer px-3 py-2 hover:bg-gray-50 flex items-center justify-between" data-id="{{ $s->id }}" data-name="{{ $s->name }}">
            <div class="text-sm text-gray-800">{{ $s->name }}</div>
            <div class="text-xs text-gray-500">#{{ $s->id }}</div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="p-3 border-t text-left">
      <button type="button" class="px-3 py-2 bg-gray-200 rounded" onclick="closeSupplierModal()">بستن</button>
    </div>
  </div>
</div>

<script>
  // دادهٔ تامین‌کننده‌ها برای مقداردهی اولیه
  const supplierMap = {
    @foreach($suppliers as $s)
      {{ $s->id }}: @json($s->name)@if(! $loop->last),@endif
    @endforeach
  };
  const supplierIdInput = document.getElementById('supplier_id');
  const supplierDisplay = document.getElementById('supplier_display');
  const btnOpenSupplier = document.getElementById('btn_open_supplier');
  function openSupplierModal(){ document.getElementById('supplier_modal').classList.remove('hidden'); document.getElementById('supplier_search').focus(); }
  function closeSupplierModal(){ document.getElementById('supplier_modal').classList.add('hidden'); }
  function attachSupplierEvents(){
    document.querySelectorAll('.supplier-row').forEach(el => {
      el.addEventListener('click', () => {
        const id = el.getAttribute('data-id');
        const name = el.getAttribute('data-name');
        supplierIdInput.value = id;
        supplierDisplay.value = name;
        closeSupplierModal();
      });
    });
  }
  function filterSuppliers(){
    const q = (document.getElementById('supplier_search').value || '').toLowerCase();
    document.querySelectorAll('.supplier-row').forEach(el => {
      const name = (el.getAttribute('data-name') || '').toLowerCase();
      el.style.display = name.indexOf(q) !== -1 ? '' : 'none';
    });
  }
  btnOpenSupplier && btnOpenSupplier.addEventListener('click', openSupplierModal);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSupplierModal(); });
  if (supplierIdInput && supplierIdInput.value && supplierMap[supplierIdInput.value]) { supplierDisplay.value = supplierMap[supplierIdInput.value]; }

  const itemsBody = document.getElementById('items-body');
  const addItemBtn = document.getElementById('add-item');
  const previouslyPaidInput = document.getElementById('previously_paid_amount');
  const totalDisplay = document.getElementById('total_amount_display');
  const vatPercentInput = document.getElementById('vat_percent');
  const vatAmountDisplay = document.getElementById('vat_amount_display');
  const grandTotalDisplay = document.getElementById('grand_total_display');
  const applyVatCheckbox = document.getElementById('apply_vat');
  const remainingDisplay = document.getElementById('remaining_display');

  function formatInt(n){ return String(Math.round(n)); }

  function recalc(){
    let sum = 0;
    itemsBody.querySelectorAll('tr').forEach(tr => {
      if (tr.id === 'item-row-template') return; // skip template row
      const qty = parseFloat(tr.querySelector('.item-qty')?.value || 0);
      const price = parseFloat(tr.querySelector('.item-price')?.value || 0);
      const lt = qty * price;
      tr.querySelector('.item-total').textContent = formatInt(lt);
      sum += lt;
    });
    totalDisplay.value = formatInt(sum);

    const vatPercent = applyVatCheckbox && applyVatCheckbox.checked ? (parseFloat(vatPercentInput.value || 0) || 0) : 0;
    const vatAmount = Math.round((sum * (vatPercent / 100)) * 100) / 100;
    if (vatAmountDisplay) vatAmountDisplay.value = formatInt(vatAmount);

    const grand = sum + vatAmount;
    if (grandTotalDisplay) grandTotalDisplay.value = formatInt(grand);

    const paid = parseFloat(previouslyPaidInput.value || 0);
    remainingDisplay.value = formatInt(Math.max(grand - paid, 0));
  }

  // Indexed row creation using a disabled template to avoid duplicate/blank names
  let nextIndex = 0;
  function addRowIndexed(item = {item_name:'', quantity:'', unit:'', unit_price:''}){
    const tpl = document.getElementById('item-row-template');
    if (!tpl) return;
    const tr = tpl.cloneNode(true);
    tr.removeAttribute('id');
    tr.classList.remove('hidden');

    tr.querySelectorAll('input, select, button').forEach(el => {
      const dataName = el.getAttribute('data-name');
      if (dataName) {
        el.name = dataName.replace('__INDEX__', String(nextIndex));
        el.removeAttribute('data-name');
      }
      el.disabled = false;
    });

    // Set values
    const nameInput = tr.querySelector('.item-name');
    const qtyInput = tr.querySelector('.item-qty');
    const unitSelect = tr.querySelector('.item-unit');
    const priceInput = tr.querySelector('.item-price');
    if (nameInput) nameInput.value = item.item_name ?? '';
    if (qtyInput) qtyInput.value = item.quantity ?? '';
    if (unitSelect) unitSelect.value = item.unit ?? '';
    if (priceInput) priceInput.value = item.unit_price ?? '';

    itemsBody.appendChild(tr);
    tr.querySelectorAll('input, select').forEach(inp => inp.addEventListener('input', recalc));
    tr.querySelector('.remove-row')?.addEventListener('click', () => { tr.remove(); recalc(); });
    nextIndex++;
    recalc();
  }

  function addRow(item = {item_name:'', quantity:1, unit:'', unit_price:0}){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-2"><input name="items[][item_name]" class="w-48 rounded-md border-gray-300 item-name" value="${item.item_name}"></td>
      <td class="px-3 py-2"><input type="number" step="1" min="0" name="items[][quantity]" class="w-24 rounded-md border-gray-300 item-qty" value="${item.quantity}"></td>
      <td class="px-3 py-2">
        <select name="items[][unit]" class="w-28 rounded-md border-gray-300 item-unit">
          <option value="متر" ${item.unit==='متر'?'selected':''}>متر</option>
          <option value="سانتی متر" ${item.unit==='سانتی متر'?'selected':''}>سانتی متر</option>
          <option value="کیلوگرم" ${item.unit==='کیلوگرم'?'selected':''}>کیلوگرم</option>
          <option value="لیتر" ${item.unit==='لیتر'?'selected':''}>لیتر</option>
          <option value="عدد" ${item.unit==='عدد'?'selected':''}>عدد</option>
        </select>
      </td>
      <td class="px-3 py-2"><input type="number" step="1" min="0" name="items[][unit_price]" class="w-28 rounded-md border-gray-300 item-price" value="${item.unit_price}"></td>
      <td class="px-3 py-2"><span class="item-total">0</span></td>
      <td class="px-3 py-2 text-right"><button type="button" class="text-red-600 remove-row">حذف</button></td>`;
    itemsBody.appendChild(tr);
    tr.querySelectorAll('input').forEach(inp => inp.addEventListener('input', recalc));
    tr.querySelector('.remove-row').addEventListener('click', () => { tr.remove(); recalc(); });
    recalc();
  }

  addItemBtn.addEventListener('click', () => addRowIndexed());
  previouslyPaidInput.addEventListener('input', recalc);
  if (applyVatCheckbox) {
    applyVatCheckbox.addEventListener('change', () => {
      if (vatPercentInput) vatPercentInput.disabled = !applyVatCheckbox.checked;
      if (!applyVatCheckbox.checked && vatPercentInput) {
        vatPercentInput.value = '';
      }
      recalc();
    });
  }
  if (vatPercentInput) vatPercentInput.addEventListener('input', recalc);

  // Restore previously entered rows after validation errors; otherwise don't add a blank row
  const oldItems = @json(old('items', []));
  if (Array.isArray(oldItems) && oldItems.length > 0) {
    oldItems.forEach(it => addRowIndexed({
      item_name: it.item_name || '',
      quantity:  it.quantity ?? '',
      unit:      it.unit || '',
      unit_price:it.unit_price ?? ''
    }));
  }
  // Restore VAT state from old input
  @if(old('vat_percent') !== null)
    if (applyVatCheckbox && vatPercentInput) {
      applyVatCheckbox.checked = true;
      vatPercentInput.disabled = false;
    }
  @endif
  attachSupplierEvents();

  // Toggle project name input based on usage_type
  const usageSelect = document.getElementById('usage_type');
  const projectWrapper = document.getElementById('project_name_wrapper');
  function toggleProjectName(){
    const v = (usageSelect?.value || '').toLowerCase();
    projectWrapper.style.display = (v === 'project' || v === 'both') ? '' : 'none';
  }
  usageSelect && usageSelect.addEventListener('change', toggleProjectName);
  // Initialize visibility from old value
  toggleProjectName();
</script>
@endsection
