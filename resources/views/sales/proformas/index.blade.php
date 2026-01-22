@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'پیش‌فاکتورها']
        ];
    @endphp

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-none">
            <div class="flex gap-3 flex-wrap items-center justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                        {{ __('پیش‌فاکتورها') }}
                    </h2>
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 px-3 py-2 text-green-700 text-xs flex items-start justify-between gap-3 dismissible-alert">
                    <span>{{ session('success') }}</span>
                    <button
                        type="button"
                        class="text-green-700 hover:text-green-900 leading-none text-lg px-1"
                        aria-label="بستن اعلان"
                        onclick="this.closest('.dismissible-alert')?.remove();"
                    >×</button>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-yellow-50 p-3 text-yellow-800 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

                    {{-- دکمه ایجاد پیش‌فاکتور --}}
                    <a href="{{ route('sales.proformas.create') }}" class="mb-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> ایجاد پیش‌فاکتور
                    </a>
                    @role('admin')
                    {{-- دکمه رفتن به صفحه ایمپورت --}}
                    <a href="{{ route('sales.proformas.import.form') }}" class="mb-4 inline-block bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                        <i class="fas fa-file-import mr-2"></i> ایمپورت پیش‌فاکتورها
                    </a>
                    <button
                        id="bulk-delete-btn"
                        form="proformas-bulk-form"
                        type="submit"
                        class="mb-4 inline-flex items-center px-4 py-2 bg-red-600 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
                        disabled
                    >
                        <i class="fas fa-trash mr-2"></i>
                        حذف گروهی
                        <span id="selected-count-badge" class="ml-2 hidden px-2 py-0.5 text-xs rounded-full bg-white/20">
                            0
                        </span>
                    </button>
                    @endrole
                </div>

                <form action="{{ route('sales.proformas.index') }}" method="GET" class="mb-4 inline-flex items-center gap-2" onsubmit="return false">
                    <input type="hidden" name="proforma_number" value="{{ request('proforma_number') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="organization_id" value="{{ request('organization_id') }}">
                    <input type="hidden" name="stage" value="{{ request('stage') }}">
                    <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
                    <input type="hidden" name="contact" value="{{ request('contact') }}">
                    <input type="hidden" name="opportunity" value="{{ request('opportunity') }}">
                    <label for="per_page" class="text-sm text-gray-700 whitespace-nowrap">تعداد در صفحه</label>
                    <select id="per_page" name="per_page" class="border rounded px-2 py-1 text-sm">
                        <option value="10"  {{ (string)request('per_page', 10) === '10'  ? 'selected' : '' }}>10</option>
                        <option value="25"  {{ (string)request('per_page', 10) === '25'  ? 'selected' : '' }}>25</option>
                        <option value="50"  {{ (string)request('per_page', 10) === '50'  ? 'selected' : '' }}>50</option>
                        <option value="100" {{ (string)request('per_page', 10) === '100' ? 'selected' : '' }}>100</option>
                    </select>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 text-xs">
                    {{-- فرم حذف گروهی --}}
                    <form id="proformas-bulk-form"
                          action="{{ route('sales.proformas.bulk-destroy') }}"
                          method="POST"
                          onsubmit="return handleBulkDeleteSubmit(event)">
                        @csrf
                        @method('DELETE')

                        <table class="min-w-full divide-y divide-gray-200 text-[13px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">
                                        <input
                                            id="select-all"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200"
                                            title="انتخاب همه"
                                        >
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">شماره</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">موضوع</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مرحله</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">سازمان</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مخاطب</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مبلغ کل</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">تاریخ پیش‌فاکتور</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">فرصت</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">ارجاع به</th>
                                    <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">عملیات</th>
                                </tr>
                                <tr>
                                    <th class="px-6 py-2"></th>
                                    <th class="px-6 py-2">
                                        <input type="text"
                                            id="filter-proforma-number"
                                            name="proforma_number"
                                            class="w-full px-2 py-1 border rounded text-sm"
                                            placeholder="شماره..."
                                            value="{{ request('proforma_number') }}"
                                        >
                                    </th>
                                    <th class="px-6 py-2">
                                        <input type="text"
                                            id="filter-search"
                                            name="search"
                                            class="w-full px-2 py-1 border rounded text-sm"
                                            placeholder="جستجو موضوع..."
                                            value="{{ request('search') }}"
                                        >
                                    </th>
                                    <th class="px-6 py-2">
                                        <select id="filter-stage" name="stage" class="w-full px-2 py-1 border rounded text-sm">
                                            <option value="">همه مراحل</option>
                                            @foreach(config('proforma.stages') as $key => $label)
                                                <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-6 py-2">
                                        @php
                                            $selectedOrgId   = request('organization_id');
                                            $selectedOrgName = null;
                                            if ($selectedOrgId) {
                                                $selected = $organizations->firstWhere('id', (int) $selectedOrgId);
                                                $selectedOrgName = $selected?->name;
                                            }
                                        @endphp
                                        <div class="relative">
                                            <input
                                                id="org-live-input"
                                                type="text"
                                                class="block w-full rounded border-gray-300 shadow-sm pr-10 text-sm"
                                                placeholder="نام سازمان..."
                                                autocomplete="off"
                                                value="{{ $selectedOrgName ?? '' }}"
                                            >

                                            <button
                                                type="button"
                                                id="org-live-clear"
                                                class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600"
                                                aria-label="پاک کردن"
                                                title="پاک کردن"
                                            >×</button>

                                            <input type="hidden" name="organization_id" id="org-live-hidden" value="{{ $selectedOrgId ?? '' }}">

                                            <ul
                                                id="org-live-list"
                                                class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded shadow max-h-60 overflow-y-auto hidden"
                                            >
                                                <li class="px-3 py-2 cursor-pointer hover:bg-gray-100" data-id="" data-name="">
                                                    همه سازمان‌ها
                                                </li>

                                                @foreach($organizations as $org)
                                                    <li
                                                        class="px-3 py-2 cursor-pointer hover:bg-gray-100"
                                                        data-id="{{ $org->id }}"
                                                        data-name="{{ $org->name }}"
                                                    >
                                                        {{ $org->name }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </th>
                                    <th class="px-6 py-2">
                                        <input type="text"
                                            id="filter-contact"
                                            name="contact"
                                            class="w-full px-2 py-1 border rounded text-sm"
                                            placeholder="مخاطب..."
                                            value="{{ request('contact') }}"
                                        >
                                    </th>
                                    <th class="px-6 py-2"></th>
                                    <th class="px-6 py-2"></th>
                                    <th class="px-6 py-2">
                                        <input type="text"
                                            id="filter-opportunity"
                                            name="opportunity"
                                            class="w-full px-2 py-1 border rounded text-sm"
                                            placeholder="فرصت..."
                                            value="{{ request('opportunity') }}"
                                        >
                                    </th>
                                    <th class="px-6 py-2">
                                        <select id="filter-assigned-to" name="assigned_to" class="w-full px-2 py-1 border rounded text-sm">
                                            <option value=""> همه </option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-6 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="proformas-tbody" class="bg-white divide-y divide-gray-200">
                                @include('sales.proformas.partials.rows', ['proformas' => $proformas])
                            </tbody>
                        </table>
                    </form>

                    <div id="proformas-pagination" class="mt-4">
                        @include('sales.proformas.partials.pagination', ['proformas' => $proformas])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="single-delete-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

    
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form       = document.getElementById('proformas-bulk-form');
    const bulkBtn    = document.getElementById('bulk-delete-btn');
    const selectAll  = document.getElementById('select-all');
    const countBadge = document.getElementById('selected-count-badge');

    function getRowChecks() {
        return Array.from(document.querySelectorAll('.row-check'));
    }

    function refresh() {
        const rowChecks = getRowChecks();
        const selected = rowChecks.filter(c => c.checked);
        const eligible = rowChecks.filter(c => !c.disabled);

        // فعال/غیرفعال شدن دکمه
        if (bulkBtn) bulkBtn.disabled = selected.length === 0;

        // شمارنده
        if (countBadge) {
            countBadge.textContent = selected.length;
            countBadge.classList.toggle('hidden', selected.length === 0);
        }

        // حالت select-all
        if (selectAll) {
            const allChecked  = eligible.length > 0 && eligible.every(c => c.checked);
            const noneChecked = eligible.every(c => !c.checked);
            selectAll.indeterminate = !(allChecked || noneChecked);
            selectAll.checked = allChecked;
        }
    }

    function bindRowChecks() {
        const rowChecks = getRowChecks();
        rowChecks.forEach(c => {
            if (c.dataset.bound === '1') return;
            c.dataset.bound = '1';
            c.addEventListener('change', refresh);
        });
    }

    if (selectAll && selectAll.dataset.bound !== '1') {
        selectAll.dataset.bound = '1';
        selectAll.addEventListener('change', e => {
            const v = e.target.checked;
            const rowChecks = getRowChecks();
            rowChecks.forEach(c => { if (!c.disabled) c.checked = v; });
            refresh();
        });
    }

    // هندل ارسال فرم (global)
    window.handleBulkDeleteSubmit = function (e) {
        const selected = getRowChecks().filter(c => c.checked);
        if (selected.length === 0) {
            e.preventDefault();
            if (window.Swal) {
                Swal.fire({ icon: 'info', text: 'هیچ موردی انتخاب نشده است.' });
            } else {
                window.confirm('هیچ موردی انتخاب نشده است.');
            }
            return false;
        }
        const confirmMsg = `آیا از حذف ${selected.length} مورد انتخابی اطمینان دارید؟`;
        if (window.Swal) {
            e.preventDefault();
            Swal.fire({
                title: 'حذف گروهی',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف'
            }).then(res => { if (res.isConfirmed) form.submit(); });
            return false;
        }
        return window.confirm(confirmMsg);
    };

    // مقدار اولیه
    bindRowChecks();
    refresh();

    window.proformasRebindBulk = function () {
        bindRowChecks();
        refresh();
    };
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form    = document.querySelector('form[action*="sales/proformas"]');
  const $input  = document.getElementById('org-live-input');
  const $list   = document.getElementById('org-live-list');
  const $hidden = document.getElementById('org-live-hidden');
  const $clear  = document.getElementById('org-live-clear');

  if (!($input && $list && $hidden && $clear)) return;

  // --- Utils ----------------------------------------------------
  const norm = (s='') => {
    // حذف نیم‌فاصله/کنترل‌های جهت و یکسان‌سازی ي/ی و ك/ک
    return String(s)
      .replace(/[\u200c\u200d\u200e\u200f]/g, '')
      .replace(/ي/g,'ی').replace(/ك/g,'ک').trim().toLowerCase();
  };

  const getItems = () => Array.from($list.querySelectorAll('li[data-id]'));
  const visibleItems = () => getItems().filter(li => li.style.display !== 'none');

  let highlightIndex = -1; // برای ناوبری کیبورد

  const clearHighlight = () => getItems().forEach(li => li.classList.remove('bg-gray-100'));
  const setHighlight = (idx) => {
    clearHighlight();
    const vis = visibleItems();
    if (!vis.length) { highlightIndex = -1; return; }
    highlightIndex = Math.max(0, Math.min(idx, vis.length - 1));
    vis[highlightIndex].classList.add('bg-gray-100');
    // اسکرول به آیتم هایلایت‌شده
    const el = vis[highlightIndex];
    const parent = $list;
    const top = el.offsetTop, bottom = top + el.offsetHeight;
    if (top < parent.scrollTop) parent.scrollTop = top;
    else if (bottom > parent.scrollTop + parent.clientHeight) parent.scrollTop = bottom - parent.clientHeight;
  };

  function showList()  { $list.classList.remove('hidden'); }
  function hideList()  { $list.classList.add('hidden');  }
  function updateClearVisibility() {
    $clear.classList.toggle('hidden', !$hidden.value);
  }

  function filterList(raw) {
    const q = norm(raw);
    let anyVisible = false;

    // اولین آیتم (همه سازمان‌ها) را هم نگه می‌داریم
    const allItem = $list.querySelector('li[data-id=""]');
    if (allItem) { allItem.style.display = q ? 'none' : 'block'; }

    getItems().forEach(li => {
      const name = li.dataset.name || li.textContent || '';
      const match = !q || norm(name).includes(q);
      li.style.display = match ? 'block' : 'none';
      if (match) anyVisible = true;
    });

    if (!anyVisible && !q) {
      // اگر جستجو خالی است ولی به هر دلیلی آیتمی نیست
      hideList();
    }
    // ریست هایلایت
    clearHighlight();
    highlightIndex = -1;
  }

  function selectLi(li) {
    if (!li) return;
    $input.value  = (li.dataset.name || '');
    $hidden.value = (li.dataset.id   || '');
    hideList();
    updateClearVisibility();
    if (typeof window.proformasApplyFilters === 'function') {
      window.proformasApplyFilters();
    }
  }

  // --- رویدادها -------------------------------------------------

  // نمایش لیست هنگام فوکوس
  $input.addEventListener('focus', () => {
    filterList($input.value);
    showList();
  });

  // فیلتر با تایپ
  $input.addEventListener('input', () => {
    filterList($input.value);
    showList();
    // انتخاب قبلی را دست‌نخورده می‌گذاریم؛ در submit بررسی می‌کنیم
    if (typeof window.proformasScheduleFilterApply === 'function') {
      window.proformasScheduleFilterApply();
    }
  });

  // ناوبری کیبورد
  $input.addEventListener('keydown', (e) => {
    const vis = visibleItems();
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (vis.length) setHighlight(highlightIndex + 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (vis.length) {
        if (highlightIndex <= 0) { clearHighlight(); highlightIndex = -1; }
        else setHighlight(highlightIndex - 1);
      }
    } else if (e.key === 'Enter') {
      const visNow = visibleItems();
      if (visNow.length) {
        e.preventDefault();
        // اگر آیتمی هایلایت است همان، وگرنه اگر فقط یکی دیده می‌شود همان
        const li = (highlightIndex >= 0 ? visNow[highlightIndex] : (visNow.length === 1 ? visNow[0] : null));
        if (li) selectLi(li);
        else hideList(); // بگذار با جستجوی متنی ارسال شود
      }
    } else if (e.key === 'Escape') {
      hideList();
    }
  });

  // انتخاب با کلیک
  $list.addEventListener('click', (e) => {
    const li = e.target.closest('li[data-id]');
    if (!li) return;
    selectLi(li);
  });

  // پاک کردن انتخاب
  $clear.addEventListener('click', () => {
    $input.value  = '';
    $hidden.value = '';
    filterList('');
    updateClearVisibility();
    $input.focus();
    showList();
    if (typeof window.proformasApplyFilters === 'function') {
      window.proformasApplyFilters();
    }
  });

  // بستن با کلیک بیرون
  document.addEventListener('click', (e) => {
    const wrap = $input.closest('.relative');
    if (!wrap || !wrap.contains(e.target)) hideList();
  });

  // پیش از ارسال فرم: اگر hidden خالی است ولی تایپ شده،
  // تلاش برای Exact Match (بعد از نرمال‌سازی) و ست‌کردن ID
  if (form) {
    form.addEventListener('submit', () => {
      if ($hidden.value || !$input.value.trim()) return;
      const q = norm($input.value);
      const exact = getItems().find(li => norm(li.dataset.name || '') === q);
      if (exact) {
        $hidden.value = exact.dataset.id || '';
      }
      // اگر exact نبود، ارسال می‌شود تا جستجوی متنی کار کند
    });
  }

  // وضعیت اولیه
  filterList($input.value);
  updateClearVisibility();
  if ($hidden.value) hideList();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const proformaNumberInput = document.getElementById('filter-proforma-number');
  const searchInput = document.getElementById('filter-search');
  const contactInput = document.getElementById('filter-contact');
  const opportunityInput = document.getElementById('filter-opportunity');
  const stageSelect = document.getElementById('filter-stage');
  const assignedSelect = document.getElementById('filter-assigned-to');
  const perPageSelect = document.getElementById('per_page');
  const orgHidden = document.getElementById('org-live-hidden');
  const tbody = document.getElementById('proformas-tbody');
  const pagination = document.getElementById('proformas-pagination');

  function buildParams() {
    const params = new URLSearchParams(window.location.search);
    const proformaNumberVal = (proformaNumberInput?.value || '').trim();
    const searchVal = (searchInput?.value || '').trim();
    const contactVal = (contactInput?.value || '').trim();
    const opportunityVal = (opportunityInput?.value || '').trim();
    const stageVal = stageSelect?.value || '';
    const assignedVal = assignedSelect?.value || '';
    const perPageVal = perPageSelect?.value || '';
    const orgIdVal = orgHidden?.value || '';

    if (proformaNumberVal) params.set('proforma_number', proformaNumberVal); else params.delete('proforma_number');
    if (searchVal) params.set('search', searchVal); else params.delete('search');
    if (contactVal) params.set('contact', contactVal); else params.delete('contact');
    if (opportunityVal) params.set('opportunity', opportunityVal); else params.delete('opportunity');
    if (stageVal) params.set('stage', stageVal); else params.delete('stage');
    if (assignedVal) params.set('assigned_to', assignedVal); else params.delete('assigned_to');
    if (perPageVal) params.set('per_page', perPageVal); else params.delete('per_page');
    if (orgIdVal) params.set('organization_id', orgIdVal); else params.delete('organization_id');

    return params;
  }

  function fetchProformas(url, replaceUrl = true) {
    const reqUrl = new URL(url, window.location.origin);
    fetch(reqUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(response => response.json())
      .then(data => {
        if (tbody) tbody.innerHTML = data.rows || '';
        if (pagination) pagination.innerHTML = data.pagination || '';
        if (typeof window.proformasRebindBulk === 'function') {
          window.proformasRebindBulk();
        }
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
    fetchProformas(url, true);
  }

  let filterTimer = null;
  function scheduleFilterApply() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(applyFilters, 300);
  }

  window.proformasApplyFilters = applyFilters;
  window.proformasScheduleFilterApply = scheduleFilterApply;

  proformaNumberInput?.addEventListener('input', scheduleFilterApply);
  searchInput?.addEventListener('input', scheduleFilterApply);
  contactInput?.addEventListener('input', scheduleFilterApply);
  stageSelect?.addEventListener('change', applyFilters);
  opportunityInput?.addEventListener('input', scheduleFilterApply);
  assignedSelect?.addEventListener('change', applyFilters);
  perPageSelect?.addEventListener('change', applyFilters);

  pagination?.addEventListener('click', function (e) {
    const link = e.target.closest('a');
    if (!link) return;
    e.preventDefault();
    fetchProformas(link.href, true);
  });
});
</script>

<script>
  // توجه: اگر button را disabled کنی، onclick اجرا نمی‌شود.
  function showEditDeleteAlert(msg) {
    alert(msg);
  }
</script>
<script>
function deleteSingleProforma(id) {
  if (!confirm('آیا از حذف این پیش‌فاکتور مطمئن هستید؟')) return;

  const form = document.getElementById('single-delete-form');

  // URL روت با نام صحیح + توکن امن برای جایگزینی
  form.action = "{{ route('sales.proformas.destroy', ['proforma' => '__ID__']) }}"
                  .replace('__ID__', id);

  form.submit();
}
</script>


@endsection
