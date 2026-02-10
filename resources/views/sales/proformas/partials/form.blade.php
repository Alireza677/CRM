{{-- همیشه این‌ها را در اولین خطوط پارشیال بگذار --}}
@php
    // تعیین حالت صفحه: ایجاد یا ویرایش
    $isEdit = $isEdit
        ?? (isset($proforma) && $proforma && method_exists($proforma, 'getKey') && $proforma->getKey());

    // جلوگیری از Undefined array key روی $prefill وقتی از create نیومده
    $prefill = $prefill ?? [];

    // مقادیر پیش‌فرض نمایش/شناسه‌ها
    $orgNameDefault = $isEdit
        ? optional($proforma->organization)->name
        : ($prefill['organization_name'] ?? '');

    $orgIdDefault = $isEdit
        ? ($proforma->organization_id ?? '')
        : ($prefill['organization_id'] ?? '');

    // ساخت نام کامل مخاطب در حالت edit؛ در حالت create از prefill می‌گیریم
    $cntNameDefault = $isEdit
        ? ( optional($proforma->contact)->full_name
            ?? trim( (optional($proforma->contact)->first_name ?? '').' '.(optional($proforma->contact)->last_name ?? '') )
          )
        : ($prefill['contact_name'] ?? '');

    $cntIdDefault = $isEdit
        ? ($proforma->contact_id ?? '')
        : ($prefill['contact_id'] ?? '');

    $oppNameDefault = $isEdit
        ? optional($proforma->opportunity)->name
        : ($prefill['opportunity_name'] ?? '');

    $oppIdDefault = $isEdit
        ? ($proforma->opportunity_id ?? '')
        : ($prefill['opportunity_id'] ?? '');
@endphp


{{-- دسته اول: اطلاعات پیش‌فاکتور --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- subject --}}
    <div class="form-group">
        <label for="subject" class="form-label">
            موضوع <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="subject" name="subject"
               value="{{ old('subject', $isEdit ? $proforma->subject : '') }}" required>
        @error('subject')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- تاریخ شمسی و مخفی میلادی --}}
    <div class="form-group">
        <label for="proforma_date_shamsi" class="form-label">تاریخ پیش فاکتور</label>
        <input type="text" class="form-control" id="proforma_date_shamsi"
               value="{{ old('proforma_date_shamsi', $isEdit ? ($proforma->proforma_date_shamsi ?? '') : '') }}"
               placeholder=" تاریخ را وارد کنید">
        <input type="hidden" name="proforma_date" id="proforma_date"
               value="{{ old('proforma_date', $isEdit ? $proforma->proforma_date : '') }}">
        @error('proforma_date')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- مرحله پیش‌فاکتور (کنترل سیستمی) --}}
    @php
        $currentStage = old('proforma_stage', $isEdit ? ($proforma->proforma_stage ?? 'draft') : 'draft');
        $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$currentStage] ?? $currentStage;
    @endphp
    <div>
        <label class="block mb-1 font-medium text-gray-700">
            مرحله پیش‌فاکتور
        </label>
        <div class="mt-2 px-3 py-2 rounded border border-dashed border-gray-300 bg-gray-50 text-gray-700 text-sm flex items-center justify-between">
            <span>{{ $stageLabel }}</span>
            <span class="text-xs text-gray-500">سیستمی</span>
        </div>
        <input type="hidden" name="proforma_stage" id="proforma_stage" value="{{ $currentStage }}">
    </div>
</div>

{{-- دسته دوم: مخاطب و فروش --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- سازمان --}}
    <div>
        <label for="organization_id" class="block font-medium text-sm text-gray-700">سازمان</label>
        <div class="flex items-center gap-2">
            <input type="text" id="organization_name" name="organization_name"
                class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                placeholder="انتخاب سازمان" readonly
                value="{{ old('organization_name', $orgNameDefault) }}">
            <input type="hidden" id="organization_id" name="organization_id"
                value="{{ old('organization_id', $orgIdDefault) }}">
            <button type="button" onclick="openOrganizationModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
        </div>
        @error('organization_id')
            <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
        @enderror
    </div>

    {{-- مخاطب --}}
    <div>
        <label for="contact_id" class="block font-medium text-sm text-gray-700">مخاطب</label>
        <div class="flex items-center gap-2">
            <input type="text" id="contact_name" name="contact_name"
                class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                placeholder="انتخاب مخاطب" readonly
                value="{{ old('contact_name', $cntNameDefault) }}">
            <input type="hidden" id="contact_id" name="contact_id"
                value="{{ old('contact_id', $cntIdDefault) }}">
            <button type="button" onclick="openContactModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
        </div>
        @error('contact_id')
            <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
        @enderror
    </div>

    {{-- فرصت فروش --}}
    <div>
        <label for="opportunity_id" class="block font-medium text-sm text-gray-700">فرصت فروش</label>
        <div class="flex items-center gap-2">
            <input type="text" id="opportunity_name" name="opportunity_name"
                class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                placeholder="انتخاب فرصت فروش" readonly
                value="{{ old('opportunity_name', $oppNameDefault) }}">
            <input type="hidden" id="opportunity_id" name="opportunity_id"
                value="{{ old('opportunity_id', $oppIdDefault) }}">
            <button type="button" onclick="openOpportunityModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
            <button type="button" onclick="openOpportunityCreateModal()" class="text-green-600 text-xl hover:text-green-800 transition" title="ایجاد فرصت فروش">+</button>
        </div>
        @error('opportunity_id')
            <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
        @enderror
    </div>

    {{-- ارجاع به --}}
    <div class="form-group">
        <label for="assigned_to" class="form-label">ارجاع به <span class="text-danger">*</span></label>
        <select class="form-control" id="assigned_to" name="assigned_to" required>
            <option value="">انتخاب کنید</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                    {{ (string)old('assigned_to', $isEdit ? $proforma->assigned_to : (auth()->id() ?? '')) === (string)$user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_to')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

  {{-- دسته سوم: اطلاعات آدرس --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {{-- استان --}}
      <div class="form-group">
          <label for="stateSelect" class="form-label">استان <span class="text-red-600">*</span></label>
          <select name="state" id="stateSelect" class="form-control mt-1">
              <option value="">انتخاب استان</option>
              @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
                  <option value="{{ $st }}" 
                      {{ old('state', $isEdit ? $proforma->state ?? '' : '') === $st ? 'selected' : '' }}>
                      {{ $st }}
                  </option>
              @endforeach
          </select>
          @error('state') 
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
          @enderror
      </div>

      {{-- شهر --}}
      <div class="form-group">
          <label for="citySelect" class="form-label">شهر</label>
          <select name="city" id="citySelect" class="form-control mt-1" 
              {{ old('state', $isEdit ? $proforma->state ?? '' : '') ? '' : 'disabled' }}>
              <option value="{{ old('state', $isEdit ? $proforma->state ?? '' : '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}">
                  {{ old('state', $isEdit ? $proforma->state ?? '' : '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}
              </option>
              @php
                  $state = old('state', $isEdit ? $proforma->state ?? '' : '');
                  $city  = old('city', $isEdit ? $proforma->city ?? '' : '');
                  $all   = \App\Helpers\FormOptionsHelper::iranLocations();
                  $list  = $state && isset($all[$state]) ? $all[$state] : [];
              @endphp
              @foreach($list as $c)
                  <option value="{{ $c }}" {{ $city === $c ? 'selected' : '' }}>{{ $c }}</option>
              @endforeach
          </select>
          @error('city') 
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
          @enderror
      </div>
  </div>

  


<div class="form-group">
    <label for="customer_address" class="form-label">آدرس مشتری</label>
    <textarea class="form-control" id="customer_address" name="customer_address" rows="3">{{ old('customer_address', $isEdit ? $proforma->customer_address : '') }}</textarea>
    @error('customer_address')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">نوع آدرس</label>
    @php
        $addrType = old('address_type', $isEdit ? ($proforma->address_type ?? 'invoice') : 'invoice');
    @endphp
    <div class="form-check">
        <input class="form-check-input" type="radio" name="address_type" id="invoice_address" value="invoice" {{ $addrType === 'invoice' ? 'checked' : '' }}>
        <label class="form-check-label" for="invoice_address">آدرس تحویل صورت‌حساب</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="address_type" id="product_address" value="product" {{ $addrType === 'product' ? 'checked' : '' }}>
        <label class="form-check-label" for="product_address">آدرس تحویل محصول</label>
    </div>
</div>

{{-- اطلاعات محصولات --}}
<div class="bg-white p-6 rounded-lg shadow-sm mt-6">
    <h3 class="text-lg font-semibold mb-4">اطلاعات محصولات</h3>
    <div id="product-rows-container" class="space-y-6">
        @if($isEdit && $proforma->items)
            @foreach($proforma->items as $item)
                @php
                    $rowId          = $item->id;
                    $name           = $item->product->name ?? $item->name ?? '';
                    $qtyValue       = old("items.$rowId.quantity", $item->quantity ?? 0);
                    $unitPriceRaw   = old("items.$rowId.unit_price", $item->unit_price ?? 0);
                    $unitPriceNum   = is_numeric($unitPriceRaw) ? (float)$unitPriceRaw : (float)str_replace([','], '', (string)$unitPriceRaw);
                    $lineTotal      = (float) $unitPriceNum * (float) $qtyValue;
                @endphp
                <div class="border p-4 rounded bg-gray-50" id="product-row-{{ $rowId }}">
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                            <input type="hidden" name="items[{{ $rowId }}][product_id]" value="{{ $item->product_id }}">

                            <div class="md:col-span-4">
                                <label class="form-label">نام محصول</label>
                                <input type="text" class="form-control h-9" value="{{ $name }}" readonly>
                                <input type="hidden" name="items[{{ $rowId }}][name]" value="{{ $name }}">
                            </div>

                            <div class="md:col-span-2">
                                <label class="form-label">قیمت واحد</label>
                                <input type="text"
                                       name="items[{{ $rowId }}][unit_price]"
                                       value="{{ is_numeric($unitPriceRaw) ? number_format((float) $unitPriceRaw) : $unitPriceRaw }}"
                                       class="form-control h-9 price-field"
                                       required>
                            </div>

                            <div class="md:col-span-1">
                                <label class="form-label">تعداد</label>
                                <input type="number"
                                       name="items[{{ $rowId }}][quantity]"
                                       class="form-control h-9 qty-field w-20 text-center"
                                       value="{{ $qtyValue }}"
                                       min="0"
                                       step="1">
                            </div>

                            <div class="md:col-span-2">
                                <label class="form-label">واحد</label>
                                <select name="items[{{ $rowId }}][unit]" class="form-control h-9 w-28 unit-field">
                                    @php
                                        $unitOld = old("items.$rowId.unit", $item->unit_of_use ?? 'device');
                                    @endphp
                                    <option value="device" {{ $unitOld === 'device' ? 'selected' : '' }}>دستگاه</option>
                                    <option value="piece"  {{ $unitOld === 'piece'  ? 'selected' : '' }}>متر</option>
                                    <option value="meter"  {{ $unitOld === 'meter'  ? 'selected' : '' }}>عدد</option>
                                </select>
                            </div>

                            <div class="md:col-span-3 flex items-end justify-between">
                                <div class="text-sm md:text-base text-gray-700 leading-6">
                                    مبلغ ردیف:
                                    <span class="line-total font-semibold" data-item-total="{{ (int) $lineTotal }}">{{ number_format((int) $lineTotal) }}</span>
                                    <span>ریال</span>
                                </div>
                                <button type="button"
                                        onclick="removeProductRow('{{ $rowId }}')"
                                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                                    حذف
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        @endif
    </div>
    <div class="flex justify-start mt-4">
        <button type="button" onclick="openProductModal()" class="btn btn-secondary">انتخاب محصول</button>
    </div>
</div>

{{-- مودال انتخاب محصول --}}
@include('sales.proformas.partials.product-modal')

@php
    $globalDiscType  = old('global_discount_type', $isEdit ? ($proforma->global_discount_type ?? '') : '');
    $globalDiscValue = old('global_discount_value', $isEdit ? ($proforma->global_discount_value ?? 0) : 0);
    $globalTaxType   = old('global_tax_type', $isEdit ? ($proforma->global_tax_type ?? '') : '');
    $globalTaxValue  = old('global_tax_value', $isEdit ? ($proforma->global_tax_value ?? 0) : 0);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 bg-white p-6 rounded-xl shadow-md border border-gray-200">
  <!-- نوع تخفیف -->
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">نوع تخفیف</label>
    <select id="discountType" name="global_discount_type"
      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-gray-700">
      <option value="" {{ $globalDiscType === '' ? 'selected' : '' }}>بدون تخفیف</option>
      <option value="percentage" {{ $globalDiscType === 'percentage' ? 'selected' : '' }}>درصدی</option>
      <option value="fixed" {{ $globalDiscType === 'fixed' ? 'selected' : '' }}>عدد ثابت</option>
    </select>
  </div>

  <!-- مقدار تخفیف -->
  <div id="discountValueWrapper">
    <label class="block text-sm font-medium text-gray-700 mb-2">مقدار تخفیف</label>
    <input type="number" name="global_discount_value" min="0"
      value="{{ $globalDiscValue }}"
      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-gray-700">
  </div>

  <!-- نوع مالیات -->
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">نوع مالیات</label>
    <select id="taxType" name="global_tax_type"
      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 px-3 py-2 text-gray-700">
      <option value="" {{ $globalTaxType === '' ? 'selected' : '' }}>بدون مالیات</option>
      <option value="percentage" {{ $globalTaxType === 'percentage' ? 'selected' : '' }}>درصدی</option>
      <option value="fixed" {{ $globalTaxType === 'fixed' ? 'selected' : '' }}>عدد ثابت</option>
    </select>
  </div>

  <!-- مقدار مالیات -->
  <div id="taxValueWrapper">
    <label class="block text-sm font-medium text-gray-700 mb-2">مقدار مالیات</label>
    <input type="number" name="global_tax_value" min="0"
      value="{{ $globalTaxValue }}"
      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 px-3 py-2 text-gray-700">
  </div>
</div>


<div class="mt-6 text-right space-y-1">
  <div>جمع جزء آیتم‌ها: <span id="items-subtotal">۰</span></div>
  <div>تخفیف سراسری: <span id="global-discount-amount">۰</span></div>
  <div>مالیات سراسری: <span id="global-tax-amount">۰</span></div>
  <div class="text-lg font-semibold">جمع کل پیش‌فاکتور: <span id="invoice-total">۰</span> ریال</div>
</div>


{{-- مودال تأیید ارسال برای تاییدیه --}}
<div id="automationConfirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" data-modal-root aria-labelledby="automationConfirmLabel" aria-hidden="true" aria-modal="true" role="dialog" hidden>
    <div class="w-full max-w-lg mx-4">
        <div class="bg-white rounded-lg shadow-lg text-end">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h5 class="modal-title" id="automationConfirmLabel">تأیید ارسال برای تاییدیه</h5>
                <button type="button" class="btn-close text-gray-500 hover:text-gray-700 text-xl leading-none" data-modal-close aria-label="بستن">&times;</button>
            </div>
            <div class="modal-body px-4 py-3">
                مرحله‌ی انتخاب‌شده "ارسال برای تاییدیه" است. آیا مطمئن هستید که می‌خواهید پیش‌فاکتور را ارسال کنید؟
            </div>
            <div class="modal-footer px-4 py-3 border-t">
                <button type="button" class="btn btn-success" id="confirm-save">بله، ارسال شود</button>
                <button type="button" class="btn btn-secondary" data-modal-close>خیر</button>
            </div>
        </div>
    </div>
</div>


{{-- ============== مودال انتخاب مخاطب ============== --}}
<div id="contactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     aria-hidden="true">
  <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
      <button type="button" onclick="closeContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
    </div>

    {{-- نوار جستجو --}}
    <div class="mb-3">
      <input id="contactSearchInput" type="text" placeholder="جستجوی نام یا موبایل…"
             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
             autocomplete="off">
      <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-100 text-gray-700 sticky top-0">
          <tr>
            <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
            <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
          </tr>
        </thead>
        <tbody id="contactTableBody">
          @foreach($contacts as $c)
            @php
                $full = trim(($c->full_name ?? '') !== '' ? $c->full_name : trim(($c->first_name ?? '').' '.($c->last_name ?? '')));
            @endphp
            <tr class="cursor-pointer hover:bg-gray-50"
                data-id="{{ $c->id }}"
                data-name="{{ $full }}"
                data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}">
              <td class="px-4 py-2 border-b border-gray-200">{{ $full ?: '—' }}</td>
              <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div id="contactNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
    </div>
  </div>
</div>

{{-- ============== مودال انتخاب سازمان ============== --}}
<div id="organizationModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     aria-hidden="true">
  <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">انتخاب سازمان</h3>
      <button type="button" onclick="closeOrganizationModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
    </div>

    {{-- نوار جستجو --}}
    <div class="mb-3">
      <input id="organizationSearchInput" type="text" placeholder="جستجوی نام سازمان یا شماره تماس…"
             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
             autocomplete="off">
      <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-100 text-gray-700 sticky top-0">
          <tr>
            <th class="px-4 py-2 border-b border-gray-300">نام سازمان</th>
            <th class="px-4 py-2 border-b border-gray-300">شماره تماس</th>
          </tr>
        </thead>
        <tbody id="organizationTableBody">
          @foreach($organizations as $org)
            <tr class="cursor-pointer hover:bg-gray-50"
                data-id="{{ $org->id }}"
                data-name="{{ $org->name }}"
                data-phone="{{ preg_replace('/\D+/', '', (string)($org->phone ?? '')) }}">
              <td class="px-4 py-2 border-b border-gray-200">{{ $org->name }}</td>
              <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $org->phone ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div id="organizationNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
    </div>
  </div>
</div>

{{-- ============== مودال انتخاب فرصت فروش ============== --}}
<div id="opportunityModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     aria-hidden="true">
  <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">انتخاب فرصت فروش</h3>
      <button type="button" onclick="closeOpportunityModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
    </div>

    {{-- نوار جستجو --}}
    <div class="mb-3">
      <input id="opportunitySearchInput" type="text" placeholder="جستجوی نام فرصت یا نام مشتری…"
             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
             autocomplete="off">
      <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-100 text-gray-700 sticky top-0">
          <tr>
            <th class="px-4 py-2 border-b border-gray-300">نام فرصت</th>
            <th class="px-4 py-2 border-b border-gray-300">مشتری</th>
            <th class="px-4 py-2 border-b border-gray-300">وضعیت</th>
          </tr>
        </thead>
        <tbody id="opportunityTableBody">
          @foreach($opportunities as $opp)
            <tr class="cursor-pointer hover:bg-gray-50"
                data-id="{{ $opp->id }}"
                data-name="{{ $opp->name }}"
                data-customer="{{ trim(($opp->contact->full_name ?? '')) }}"
                data-status="{{ $opp->status_label ?? '' }}">
              <td class="px-4 py-2 border-b border-gray-200">{{ $opp->name }}</td>
              <td class="px-4 py-2 border-b border-gray-200 text-gray-600">{{ $opp->contact->full_name ?? '—' }}</td>
              <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $opp->status_label ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div id="opportunityNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
    </div>
  </div>
</div>


{{-- مقداردهی اولیه محصولات در حالت ویرایش --}}
@if($isEdit)
    @push('scripts')
        <script>
            window.initialProducts = {!! json_encode(
                $proforma->items->map(fn($it) => [
                    'id'             => $it->id,
                    'product_id'     => $it->product_id,
                    'name'           => optional($it->product)->name,
                    'unit'           => $it->unit,
                    'qty'            => $it->qty,
                    'qty'            => (int) ($it->qty ?? 0),
                    'price'          => (int) ($it->price ?? 0),
                    'tax_type'       => $it->tax_type,
                    'tax_value'      => (int) ($it->tax_value ?? 0),
                    'discount_type'  => $it->discount_type,
                    'discount_value' => (int) ($it->discount_value ?? 0),
                ])
            , JSON_UNESCAPED_UNICODE) !!};
            (function () {
              if (!Array.isArray(window.initialProducts)) return;
              window.initialProducts = window.initialProducts.map(p => ({
                ...p,
                qty:            parseInt(p?.qty ?? 0) || 0,
                price:          parseInt(p?.price ?? 0) || 0,
                tax_value:      parseInt(p?.tax_value ?? 0) || 0,
                discount_value: parseInt(p?.discount_value ?? 0) || 0,
              }));
            })();
        </script>
    @endpush
@endif

@push('scripts')
<script>
/* ---------- باز/بستن مودال با فوکوس ---------- */
function toggleModal(modalId, open = true, focusInputId = null) {
  const el = document.getElementById(modalId);
  if (!el) return;
  if (open) {
    el.classList.remove('hidden'); el.classList.add('flex');
    el.setAttribute('aria-hidden', 'false');
    if (focusInputId) setTimeout(() => {
      const inp = document.getElementById(focusInputId);
      if (inp) inp.focus();
    }, 10);
  } else {
    el.classList.add('hidden'); el.classList.remove('flex');
    el.setAttribute('aria-hidden', 'true');
  }
}

/* ---------- هلسپرهای باز/بستن ---------- */
function openContactModal(){ toggleModal('contactModal', true, 'contactSearchInput'); }
function closeContactModal(){ toggleModal('contactModal', false); }

function openOrganizationModal(){ toggleModal('organizationModal', true, 'organizationSearchInput'); }
function closeOrganizationModal(){ toggleModal('organizationModal', false); }

function openOpportunityModal(){ toggleModal('opportunityModal', true, 'opportunitySearchInput'); }
function closeOpportunityModal(){ toggleModal('opportunityModal', false); }

/* ---------- نوشتن مقادیر در فیلدهای مقصد ---------- */
function setValueAndNotify(el, val) {
  if (!el) return;
  el.value = val ?? '';
  el.dispatchEvent(new Event('input',  { bubbles: true }));
  el.dispatchEvent(new Event('change', { bubbles: true }));
}
function pick(idOrName) {
  return document.getElementById(idOrName) || document.querySelector(`[name="${idOrName}"]`);
}
// انتخاب سازمان
function selectOrganization(id, name){
  setValueAndNotify(pick('organization_id'),   id);
  setValueAndNotify(pick('organization_name'), name);
  closeOrganizationModal();
}

// انتخاب مخاطب
function selectContact(id, name){
  setValueAndNotify(pick('contact_id'),   id);
  setValueAndNotify(pick('contact_name'), name);
  closeContactModal();
}

// انتخاب فرصت فروش
function selectOpportunity(id, name){
  setValueAndNotify(pick('opportunity_id'),   id);
  setValueAndNotify(pick('opportunity_name'), name);
  closeOpportunityModal();
}

/* ---------- بستن با کلیک روی بک‌دراپ ---------- */
document.addEventListener('click', function(e){
  ['contactModal','organizationModal','opportunityModal'].forEach(mid => {
    const m = document.getElementById(mid);
    if (!m) return;
    if (!m.classList.contains('hidden') && e.target === m) {
      toggleModal(mid, false);
    }
  });
});

/* ---------- بستن با ESC ---------- */
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') {
    toggleModal('contactModal', false);
    toggleModal('organizationModal', false);
    toggleModal('opportunityModal', false);
  }
});

/* ---------- نرمال‌سازی ارقام و جستجو ---------- */
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
function stripSeparators(str) {
  return String(str)
    .replace(/[\u200C\u200B\u00A0\s]/g, '')
    .replace(/[,\u060C]/g, '')
    .replace(/[.\u066B\u066C]/g, '');
}
function normalizeQuery(raw) {
  const lowered = String(raw || '').toLowerCase().trim();
  const digitsFixed = normalizeDigits(lowered);
  return { text: digitsFixed, numeric: stripSeparators(digitsFixed) };
}

/* ---------- سازنده‌ی فیلتر لایو ---------- */
function makeLiveFilter({inputId, tbodyId, noResultId, nameAttr='data-name', phoneAttr='data-phone', extraAttrs=[]}) {
  const $input = document.getElementById(inputId);
  const $tbody = document.getElementById(tbodyId);
  const $noRes = document.getElementById(noResultId);
  if (!$input || !$tbody) return;

  let t = null;
  $input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(applyFilter, 150); });

  function applyFilter() {
    const { text, numeric } = normalizeQuery($input.value);
    const rows = Array.from($tbody.querySelectorAll('tr'));
    if (!text) {
      rows.forEach(tr => tr.classList.remove('hidden'));
      if ($noRes) $noRes.classList.add('hidden');
      return;
    }
    let visible = 0;
    const isPureNumber = /^[0-9]+$/.test(numeric);

    rows.forEach(tr => {
      const name   = String(tr.getAttribute(nameAttr)  || '').toLowerCase();
      const phone  = String(tr.getAttribute(phoneAttr) || '');
      const extras = extraAttrs.map(a => String(tr.getAttribute(a) || '').toLowerCase()).join(' | ');

      const byName   = name.includes(text) || (extras && extras.includes(text));
      const byPhone  = isPureNumber ? phone.includes(numeric) : (numeric ? phone.includes(numeric) : false);
      const match = byName || byPhone;

      if (match) { tr.classList.remove('hidden'); visible++; }
      else { tr.classList.add('hidden'); }
    });

    if ($noRes) $noRes.classList.toggle('hidden', visible !== 0);
  }
}

/* ---------- کلیک روی ردیف‌ها (بدون inline onclick) ---------- */
function enableRowClickSelect(tbodyId, onPick) {
  const $tbody = document.getElementById(tbodyId);
  if (!$tbody) return;
  $tbody.addEventListener('click', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    const id = tr.getAttribute('data-id');
    const name = tr.getAttribute('data-name');
    if (id && onPick) onPick(id, name);
  });
}

/* ---------- فعال‌سازی پس از لود ---------- */
document.addEventListener('DOMContentLoaded', function () {
  // مخاطب
  makeLiveFilter({
    inputId: 'contactSearchInput',
    tbodyId: 'contactTableBody',
    noResultId: 'contactNoResults'
  });
  enableRowClickSelect('contactTableBody', selectContact);

  // سازمان
  makeLiveFilter({
    inputId: 'organizationSearchInput',
    tbodyId: 'organizationTableBody',
    noResultId: 'organizationNoResults'
  });
  enableRowClickSelect('organizationTableBody', selectOrganization);

  // فرصت فروش (با فیلدهای اضافه برای جستجو: customer/status)
  makeLiveFilter({
    inputId: 'opportunitySearchInput',
    tbodyId: 'opportunityTableBody',
    noResultId: 'opportunityNoResults',
    nameAttr: 'data-name',
    phoneAttr: 'data-customer', // برای اینکه ورودی عددی مشتری نزنیم، اما ساختار یکسان بماند
    extraAttrs: ['data-customer','data-status']
  });
  enableRowClickSelect('opportunityTableBody', selectOpportunity);
});
</script>

<script>
  (function(){
      const locations = @json(\App\Helpers\FormOptionsHelper::iranLocations());
      const stateEl = document.getElementById('stateSelect');
      const cityEl  = document.getElementById('citySelect');

      function fillCities(st, preset = '') {
          cityEl.innerHTML = '';
          if (!st || !locations[st]) {
              cityEl.disabled = true;
              cityEl.insertAdjacentHTML('beforeend','<option value="">ابتدا استان را انتخاب کنید</option>');
              return;
          }
          cityEl.disabled = false;
          cityEl.insertAdjacentHTML('beforeend','<option value="">انتخاب شهر</option>');
          locations[st].forEach(function(c){
              const opt = document.createElement('option');
              opt.value = c; 
              opt.textContent = c;
              if (preset && preset === c) opt.selected = true;
              cityEl.appendChild(opt);
          });
      }

      stateEl.addEventListener('change', function(){
          fillCities(this.value);
      });

      // اگر حالت ویرایش بود
      fillCities(stateEl.value, @json(old('city', $isEdit ? $proforma->city ?? '' : '')));
  })();
  </script>
 <script>
  const discountType = document.getElementById('discountType');
  const discountValueWrapper = document.getElementById('discountValueWrapper');
  const taxType = document.getElementById('taxType');
  const taxValueWrapper = document.getElementById('taxValueWrapper');

  function toggleField(selectEl, wrapperEl) {
    if (!selectEl.value) {
      wrapperEl.style.display = 'none';
    } else {
      wrapperEl.style.display = '';
    }
  }

  // بار اول صفحه لود میشه
  toggleField(discountType, discountValueWrapper);
  toggleField(taxType, taxValueWrapper);

  // تغییر مقدار
  discountType.addEventListener('change', () => toggleField(discountType, discountValueWrapper));
  taxType.addEventListener('change', () => toggleField(taxType, taxValueWrapper));
</script>

    {{-- اسکریپت محاسبات و مدیریت ردیف‌ها --}}
    @include('sales.proformas.partials.product-scripts')
@endpush
