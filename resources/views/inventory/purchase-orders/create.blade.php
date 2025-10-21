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

    <form action="{{ route('inventory.purchase-orders.store') }}" method="POST" id="po-form">
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

        <div>
          <label for="status" class="block text-sm font-medium mb-1">وضعیت</label>
          <select name="status" id="status" class="w-full rounded-md border-gray-300">
            <option value="created">ایجاد شده</option>
            <option value="approved">تأیید شده</option>
            <option value="delivered">تحویل شده</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label for="description" class="block text-sm font-medium mb-1">توضیحات</label>
          <textarea name="description" id="description" rows="3" class="w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
        </div>
      </div>

      <div class="mt-8 bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">آیتم‌ها</h3>
          <button type="button" id="add-item" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">افزودن آیتم</button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm" id="items-table">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-right">نام کالا/خدمت</th>
                <th class="px-3 py-2 text-right">تعداد</th>
                <th class="px-3 py-2 text-right">واحد</th>
                <th class="px-3 py-2 text-right">قیمت واحد</th>
                <th class="px-3 py-2 text-right">مبلغ ردیف</th>
                <th class="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody id="items-body"></tbody>
          </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
          <div>
            <label for="previously_paid_amount" class="block text-sm font-medium mb-1">مبلغ‌های پرداخت‌شده قبلی</label>
            <input type="number" step="0.01" min="0" name="previously_paid_amount" id="previously_paid_amount" value="{{ old('previously_paid_amount', 0) }}" class="w-full rounded-md border-gray-300">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">جمع کل</label>
            <input type="text" id="total_amount_display" class="w-full rounded-md border-gray-300 bg-gray-100" readonly>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">مانده قابل پرداخت</label>
            <input type="text" id="remaining_display" class="w-full rounded-md border-gray-300 bg-gray-100" readonly>
          </div>
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
  const remainingDisplay = document.getElementById('remaining_display');

  function format(n){ return (Math.round(n * 100) / 100).toFixed(2); }

  function recalc(){
    let sum = 0;
    itemsBody.querySelectorAll('tr').forEach(tr => {
      const qty = parseFloat(tr.querySelector('.item-qty')?.value || 0);
      const price = parseFloat(tr.querySelector('.item-price')?.value || 0);
      const lt = qty * price;
      tr.querySelector('.item-total').textContent = format(lt);
      sum += lt;
    });
    totalDisplay.value = format(sum);
    const paid = parseFloat(previouslyPaidInput.value || 0);
    remainingDisplay.value = format(Math.max(sum - paid, 0));
  }

  function addRow(item = {item_name:'', quantity:1, unit:'', unit_price:0}){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-2"><input name="items[][item_name]" class="w-48 rounded-md border-gray-300 item-name" value="${item.item_name}"></td>
      <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="items[][quantity]" class="w-24 rounded-md border-gray-300 item-qty" value="${item.quantity}"></td>
      <td class="px-3 py-2"><input name="items[][unit]" class="w-28 rounded-md border-gray-300 item-unit" value="${item.unit}"></td>
      <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="items[][unit_price]" class="w-28 rounded-md border-gray-300 item-price" value="${item.unit_price}"></td>
      <td class="px-3 py-2"><span class="item-total">0.00</span></td>
      <td class="px-3 py-2 text-right"><button type="button" class="text-red-600 remove-row">حذف</button></td>`;
    itemsBody.appendChild(tr);
    tr.querySelectorAll('input').forEach(inp => inp.addEventListener('input', recalc));
    tr.querySelector('.remove-row').addEventListener('click', () => { tr.remove(); recalc(); });
    recalc();
  }

  addItemBtn.addEventListener('click', () => addRow());
  previouslyPaidInput.addEventListener('input', recalc);
  addRow();
  attachSupplierEvents();
</script>
@endsection

