@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [['title' => 'مخاطبین']];
@endphp

<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-none">
        <div class="flex gap-3 flex-wrap items-center justify-between">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">مخاطبین</h2>
                <!-- Create, Import & Export Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('sales.contacts.create') }}"
                    class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-plus ml-1"></i> ایجاد مخاطب جدید
                    </a>
                    <a href="{{ route('sales.contacts.duplicates.index') }}"
                    class="mb-4 inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        <i class="fas fa-copy ml-1"></i> یافتن تکراری ها
                    </a>
                    @role('admin')
                        <a href="{{ route('sales.contacts.import.form') }}"
                        class="mb-4 inline-block bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">
                            <i class="fas fa-arrow-down ml-1"></i> ایمپورت مخاطبین
                        </a>

                   
                        <a href="{{ route('sales.contacts.export.format', ['format' => 'csv']) }}"
                        class="mb-4 inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            <i class="fas fa-file-csv ml-1"></i> اکسپورت (CSV)
                        </a>

                        {{-- XLSX --}}
                        <a href="{{ route('sales.contacts.export.format', ['format' => 'xlsx']) }}"
                        class="mb-4 inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            <i class="fas fa-file-excel ml-1"></i> اکسپورت (XLSX)
                        </a>
                        @endrole
                        <!-- حذف گروهی -->
                </div>
            </div>

            <form method="GET" action="{{ route('sales.contacts.index') }}" class="mb-4 inline-flex items-center gap-2">
                <input type="hidden" name="contact_number" value="{{ request('contact_number') }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="mobile" value="{{ request('mobile') }}">
                <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
                <input type="hidden" name="organization" value="{{ request('organization') }}">
                <input type="hidden" name="organization_name" value="{{ request('organization_name') }}">
                @php $currentPerPage = (int) request('per_page', 100); @endphp
                <label for="per_page" class="text-sm text-gray-700 whitespace-nowrap">تعداد در صفحه</label>
                <select id="per_page" name="per_page" class="border rounded px-2 py-1 text-sm">
                    @foreach([25,50,100,200] as $size)
                        <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <form method="POST" action="{{ route('sales.contacts.bulk_delete') }}" id="bulk-delete-form">
            @csrf
            @method('DELETE')

            <button type="submit"
                onclick="return confirm('آیا از حذف مخاطبین انتخاب‌شده مطمئن هستید؟')"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm mb-2">
                <i class="fas fa-trash-alt ml-1"></i> حذف گروهی
            </button>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr >
                                <th class="px-4 py-3">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">شماره</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">موبایل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">سازمان</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ارجاع به</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ ایجاد</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                            </tr>
                            <tr >
                                <th class="px-4 py-2"></th>
                                <th class="px-6 py-2">
                                    <input type="text" id="filter-contact-number" name="contact_number" placeholder="شماره..." value="{{ request('contact_number') }}"
                                           class="w-full px-2 py-1 border rounded text-sm">
                                </th>
                                <th class="px-6 py-2">
                                    <input type="text" id="filter-search" name="search" placeholder="نام..." value="{{ request('search') }}"
                                           class="w-full px-2 py-1 border rounded text-sm">
                                </th>
                                <th class="px-6 py-2">
                                    <input type="text" id="filter-mobile" name="mobile" placeholder="موبایل..." value="{{ request('mobile') }}"
                                           class="w-full px-2 py-1 border rounded text-sm">
                                </th>
                                <th class="px-6 py-2">
                                    @php
                                        // برای پرکردن پیش‌فرض اگر کوئری 'organization' وجود دارد
                                        $selectedOrgId = request('organization');
                                        $selectedOrgName = '';
                                        if ($selectedOrgId) {
                                            $selected = collect($organizations)->firstWhere('id', (int) $selectedOrgId);
                                            $selectedOrgName = $selected ? $selected->name : '';
                                        } else {
                                            $selectedOrgName = request('organization_name', '');
                                        }
                                    @endphp
                                    <div class="relative">
                                        <input
                                            type="text"
                                            id="org-filter-input"
                                            name="organization_name"
                                            placeholder="جستجوی سازمان..."
                                            class="border rounded px-2 py-1 w-full text-sm"
                                            value="{{ $selectedOrgName }}"
                                            autocomplete="off"
                                        />
                                        <input type="hidden" name="organization" id="org-id-input" value="{{ $selectedOrgId }}"/>
                                        <button type="button" id="org-clear"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                            ×
                                        </button>
                                        <ul id="org-filter-list"
                                            class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-y-auto hidden">
                                            <li>
                                                <button type="button"
                                                        class="org-item w-full text-right px-3 py-2 hover:bg-gray-100"
                                                        data-id=""
                                                        data-name="">
                                                    همه سازمان‌ها
                                                </button>
                                            </li>
                                            @foreach($organizations as $org)
                                                <li>
                                                    <button type="button"
                                                            class="org-item w-full text-right px-3 py-2 hover:bg-gray-100"
                                                            data-id="{{ $org->id }}"
                                                            data-name="{{ $org->name }}">
                                                        {{ $org->name }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </th>
                                <th class="px-6 py-2">
                                    <select id="filter-assigned-to" name="assigned_to" class="w-full px-2 py-1 border rounded text-sm">
                                        <option value="">همه ارجاع‌ها</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </th>
                                <th class="px-6 py-2 text-center"></th>
                                <th class="px-6 py-2 text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="contacts-tbody" class="bg-white divide-y divide-gray-200">
                            @include('sales.contacts.partials.rows', ['contacts' => $contacts])
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div id="contacts-pagination" class="mt-4">
                        @include('sales.contacts.partials.pagination', ['contacts' => $contacts])
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



<script>
    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.select-contact').forEach(cb => cb.checked = this.checked);
    });
</script>
<script>
(function () {
  const input   = document.getElementById('org-filter-input');
  const hidden  = document.getElementById('org-id-input');
  const list    = document.getElementById('org-filter-list');
  const clearBtn= document.getElementById('org-clear');
  const numberInput = document.getElementById('filter-contact-number');
  const searchInput = document.getElementById('filter-search');
  const mobileInput = document.getElementById('filter-mobile');
  const assignedSelect = document.getElementById('filter-assigned-to');
  const perPageSelect = document.getElementById('per_page');
  const tbody = document.getElementById('contacts-tbody');
  const pagination = document.getElementById('contacts-pagination');

  if (!input || !hidden || !list) return;

  function buildParams() {
    const params = new URLSearchParams(window.location.search);
    const numberVal = (numberInput?.value || '').trim();
    const searchVal = (searchInput?.value || '').trim();
    const mobileVal = (mobileInput?.value || '').trim();
    const assignedVal = assignedSelect?.value || '';
    const orgIdVal = hidden.value || '';
    const orgNameVal = (input.value || '').trim();
    const perPageVal = perPageSelect?.value || '';

    if (numberVal) params.set('contact_number', numberVal); else params.delete('contact_number');
    if (searchVal) params.set('search', searchVal); else params.delete('search');
    if (mobileVal) params.set('mobile', mobileVal); else params.delete('mobile');
    if (assignedVal) params.set('assigned_to', assignedVal); else params.delete('assigned_to');
    if (orgIdVal) params.set('organization', orgIdVal); else params.delete('organization');
    if (orgNameVal) params.set('organization_name', orgNameVal); else params.delete('organization_name');
    if (perPageVal) params.set('per_page', perPageVal); else params.delete('per_page');

    return params;
  }

  function fetchContacts(url, replaceUrl = true) {
    const reqUrl = new URL(url, window.location.origin);
    fetch(reqUrl.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(response => response.json())
      .then(data => {
        if (tbody) tbody.innerHTML = data.rows || '';
        if (pagination) pagination.innerHTML = data.pagination || '';
        if (replaceUrl) {
          history.replaceState(null, '', reqUrl.toString());
        }
      })
      .catch(() => {
        window.location.search = reqUrl.search;
      });
  }

  function applyFilters() {
    const params = buildParams();
    params.delete('page');
    const query = params.toString();
    const url = window.location.pathname + (query ? '?' + query : '');
    fetchContacts(url, true);
  }

  let filterTimer = null;
  function scheduleFilterApply() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(applyFilters, 300);
  }

  const items = Array.from(list.querySelectorAll('.org-item')).map(btn => ({
    el: btn,
    id: btn.getAttribute('data-id') || '',
    name: (btn.getAttribute('data-name') || btn.textContent || '').trim()
  }));

  function showList() { list.classList.remove('hidden'); }
  function hideList() { list.classList.add('hidden'); }
  function updateClearVisibility() {
    if (hidden.value) clearBtn.classList.remove('hidden');
    else clearBtn.classList.add('hidden');
  }

  function filterList(term) {
    const q = (term || '').toLowerCase().trim();
    items.forEach(({el, name}) => {
      el.parentElement.style.display = (!q || name.toLowerCase().includes(q)) ? '' : 'none';
    });
  }

  // انتخاب آیتم
  list.addEventListener('click', function (e) {
    const btn = e.target.closest('.org-item');
    if (!btn) return;
    const id   = btn.getAttribute('data-id') || '';
    const name = (btn.getAttribute('data-name') || '').trim();

    hidden.value = id;       // مقدار واقعی برای ارسال
    input.value  = name;     // نمایش نام انتخاب‌شده
    updateClearVisibility();
    hideList();
    applyFilters();
  });

  // تایپ برای فیلتر
  input.addEventListener('input', function () {
    filterList(this.value);
    showList();
    // اگر کاربر تایپ کرد، انتخاب قبلی را باطل نکن—فقط موقع سابمیت مهم است.
    scheduleFilterApply();
  });

  // فوکوس/بلور برای نمایش/مخفی‌سازی لیست
  input.addEventListener('focus', function () {
    filterList(this.value);
    showList();
  });
  document.addEventListener('click', function (e) {
    if (!list.contains(e.target) && e.target !== input) hideList();
  });

  // پاک کردن انتخاب
  clearBtn.addEventListener('click', function () {
    hidden.value = '';
    input.value  = '';
    filterList('');
    updateClearVisibility();
    input.focus();
    showList();
    applyFilters();
  });

  // وضعیت اولیه
  filterList(input.value);
  updateClearVisibility();

  numberInput?.addEventListener('input', scheduleFilterApply);
  searchInput?.addEventListener('input', scheduleFilterApply);
  mobileInput?.addEventListener('input', scheduleFilterApply);
  assignedSelect?.addEventListener('change', applyFilters);
  perPageSelect?.addEventListener('change', applyFilters);
  pagination?.addEventListener('click', function (e) {
    const link = e.target.closest('a');
    if (!link) return;
    e.preventDefault();
    fetchContacts(link.href, true);
  });
})();
</script>

<!-- SMS List Modal -->
<div id="smsListModal" class="fixed inset-0 bg-black/50 z-40 hidden items-center justify-center">
  <div class="bg-white rounded shadow-lg w-full max-w-md mx-4 p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold">افزودن مخاطب به لیست پیامک</h3>
      <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeSmsListModal()">✕</button>
    </div>

    <form id="smsListForm" method="POST" action="#">
      @csrf
      <input type="hidden" name="contact_ids[]" id="smsContactId" value="">

      @if(isset($smsLists) && $smsLists->count())
        <label class="block text-sm font-medium text-gray-700 mb-1">یک لیست را انتخاب کنید</label>
        <select id="smsListSelect" class="form-select w-full mb-4">
          @foreach($smsLists as $l)
            <option value="{{ $l->id }}">{{ $l->name }}</option>
          @endforeach
        </select>
        <div class="flex items-center justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300" onclick="closeSmsListModal()">انصراف</button>
          <button type="submit" class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">افزودن</button>
        </div>
      @else
        <div class="p-3 rounded bg-yellow-50 text-yellow-800 mb-3">
          هیچ لیست پیامکی وجود ندارد. از مسیر ابزارها » پیامک، یک لیست بسازید.
        </div>
        <div class="flex items-center justify-end">
          <button type="button" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300" onclick="closeSmsListModal()">بستن</button>
        </div>
      @endif
    </form>
  </div>
  <script>
    const smsModalEl   = document.getElementById('smsListModal');
    const smsFormEl    = document.getElementById('smsListForm');
    const smsContactEl = document.getElementById('smsContactId');
    const smsSelectEl  = document.getElementById('smsListSelect');
    const smsActionBase= "{{ url('/tools/sms/lists') }}"; // /tools/sms/lists/{id}/contacts

    function openSmsListModal(contactId, contactName) {
      if (!smsModalEl) return;
      if (smsContactEl) smsContactEl.value = contactId;
      smsModalEl.classList.remove('hidden');
      smsModalEl.classList.add('flex');
    }
    function closeSmsListModal() {
      if (!smsModalEl) return;
      smsModalEl.classList.add('hidden');
      smsModalEl.classList.remove('flex');
    }
    if (smsFormEl) {
      smsFormEl.addEventListener('submit', function (e) {
        if (!smsSelectEl || !smsSelectEl.value) return; // let server handle if missing
        // Build action like: /tools/sms/lists/{listId}/contacts
        this.action = smsActionBase + '/' + encodeURIComponent(smsSelectEl.value) + '/contacts';
      });
    }
  </script>
</div>

@endsection
