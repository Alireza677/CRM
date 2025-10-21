@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'پیش‌فاکتورها']
        ];
    @endphp

    <div class="py-6">
        <div class="w-full px-4">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                {{ __('پیش‌فاکتورها') }}
            </h2>
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-700 text-sm">
                    {{ session('success') }}
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

            <!-- Create / Import / Bulk Delete -->
            <div class="mb-4 flex flex-wrap items-center gap-2">

                {{-- دکمه ایجاد پیش‌فاکتور --}}
                <a href="{{ route('sales.proformas.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> ایجاد پیش‌فاکتور
                </a>
                @role('admin')
                {{-- دکمه رفتن به صفحه ایمپورت --}}
                <a href="{{ route('sales.proformas.import.form') }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                    <i class="fas fa-file-import mr-2"></i> ایمپورت پیش‌فاکتورها
                </a>

                
                    <button
                        id="bulk-delete-btn"
                        form="proformas-bulk-form"
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
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

            <!-- Search and Filters -->
            <div class="mb-4">
                <form action="{{ route('sales.proformas.index') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">

                    <!-- جستجو در موضوع یا سازمان -->
                    <input type="text"
                        name="search"
                        class="border rounded px-3 py-1 w-full sm:w-64"
                        placeholder="جستجو در موضوع یا نام سازمان..."
                        value="{{ request('search') }}"
                    >

                    <!-- فیلتر سازمان -->
                    {{-- فیلتر سازمان (لایو) --}}
                        @php
                            $selectedOrgId   = request('organization_id');
                            $selectedOrgName = null;
                            if ($selectedOrgId) {
                                $selected = $organizations->firstWhere('id', (int) $selectedOrgId);
                                $selectedOrgName = $selected?->name;
                            }
                        @endphp

                        <div class="relative w-full md:max-w-sm">
                            <!-- <label for="org-live-input" class="block text-sm font-medium text-gray-700">سازمان</label> -->

                            <div class="mt-1 relative">
                                <input
                                    id="org-live-input"
                                    type="text"
                                    class="block w-full rounded-md border-gray-300 shadow-sm pr-10 focus:border-primary focus:ring-primary"
                                    placeholder="نام سازمان را تایپ کنید…"
                                    autocomplete="off"
                                    value="{{ $selectedOrgName ?? '' }}"
                                >

                                {{-- دکمه پاک کردن --}}
                                <button
                                    type="button"
                                    id="org-live-clear"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600"
                                    aria-label="پاک کردن"
                                    title="پاک کردن"
                                >×</button>

                                {{-- مقدار ارسالی (ID) --}}
                                <input type="hidden" name="organization_id" id="org-live-hidden" value="{{ $selectedOrgId ?? '' }}">

                                {{-- لیست نتایج --}}
                                <ul
                                    id="org-live-list"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow max-h-60 overflow-y-auto hidden"
                                >
                                    {{-- گزینه «همه سازمان‌ها» برای پاک کردن سریع --}}
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
                        </div>


                    <!-- فیلتر مرحله -->
                    <select name="stage" class="border rounded px-2 py-1">
                        <option value="">همه مراحل</option>
                        @foreach(config('proforma.stages') as $key => $label)
                            <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <!-- تعداد آیتم در هر صفحه -->
                    <label for="per-page" class="text-gray-600">تعداد در صفحه:</label>
                    <select id="per-page" name="per_page" class="border rounded px-2 py-1" onchange="this.form.submit()">
                        <option value="10"  {{ (string)request('per_page', 10) === '10'  ? 'selected' : '' }}>10</option>
                        <option value="25"  {{ (string)request('per_page', 10) === '25'  ? 'selected' : '' }}>25</option>
                        <option value="50"  {{ (string)request('per_page', 10) === '50'  ? 'selected' : '' }}>50</option>
                        <option value="100" {{ (string)request('per_page', 10) === '100' ? 'selected' : '' }}>100</option>
                    </select>

                    <!-- فیلتر ارجاع به -->
                    <select name="assigned_to" class="border rounded px-2 py-1">
                        <option value="">ارجاع به</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- دکمه جستجو -->
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        جستجو
                    </button>

                    <!-- دکمه بازنشانی -->
                    <a href="{{ route('sales.proformas.index') }}" class="bg-gray-300 text-gray-800 px-3 py-2 rounded hover:bg-gray-400">
                        بازنشانی
                    </a>

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
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                    $stageColors = [
                                        // Generic/legacy keys
                                        'created' => 'bg-blue-100 text-blue-800',
                                        'accepted' => 'bg-green-100 text-green-800',
                                        'delivered' => 'bg-purple-100 text-purple-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'expired' => 'bg-gray-100 text-gray-800',

                                        // Proforma stages
                                        // "ارسال برای تاییدیه"
                                        'send_for_approval' => 'bg-amber-100 text-amber-800',
                                        // "در انتظار تایید نهایی" → Yellow background
                                        'awaiting_second_approval' => 'bg-yellow-100 text-yellow-800',
                                        // "تایید شده" → Green background
                                        'approved' => 'bg-green-100 text-green-800',
                                    ];
                                @endphp

                                @foreach($proformas as $proforma)
                                @php
                                    $stageKey   = $proforma->proforma_stage ?? 'unknown';
                                    $stageClass = $stageColors[$stageKey] ?? 'bg-gray-100 text-gray-800';
                                    $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$stageKey] ?? $stageKey;

                                    $locked = ($proforma->proforma_stage === 'send_for_approval'); // قابل حذف نیست
                                @endphp

                                    <tr>
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                class="row-check rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200"
                                                name="ids[]"
                                                value="{{ $proforma->id }}"
                                                {{ $locked ? 'disabled' : '' }}
                                                title="{{ $locked ? 'در وضعیت تایید: قابل حذف نیست' : 'انتخاب' }}"
                                            >
                                        </td>

                                        <td class="px-6 py-4 font-mono text-sm text-gray-700">
                                            {{ $proforma->proforma_number ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $proforma->subject }}
                                            </a>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stageClass }}">
                                                {{ $stageLabel }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">{{ $proforma->organization_name ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $proforma->contact_name ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ number_format($proforma->total_amount) }} ریال</td>
                                        <td class="px-6 py-4">
                                            @php
                                                $dateOut = '-';
                                                if ($proforma->proforma_date) {
                                                    try {
                                                        $c = \Carbon\Carbon::parse($proforma->proforma_date);
                                                        // Guard against corrupted years like 1404 AD
                                                        if ($c->year >= 1700 && $c->year <= 2500) {
                                                            $dateOut = \Morilog\Jalali\Jalalian::fromCarbon($c)->format('Y/m/d');
                                                        }
                                                    } catch (\Throwable $e) {
                                                        $dateOut = '-';
                                                    }
                                                }
                                            @endphp
                                            {{ $dateOut }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($proforma->opportunity)
                                                <a href="{{ route('sales.opportunities.show', $proforma->opportunity) }}" class="text-blue-600 hover:underline">
                                                    {{ $proforma->opportunity->name ?? ('فرصت #'.$proforma->opportunity->id) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">{{ $proforma->assignedTo->name ?? '-' }}</td>
                                        @php
                                            $canEdit = method_exists($proforma, 'canEdit')
                                                ? $proforma->canEdit()
                                                : (strtolower((string)($proforma->approval_stage ?? $proforma->proforma_stage ?? '')) === 'draft');
                                        @endphp

                                        <td class="px-6 py-4">
                                                <div class="flex items-center space-x-reverse space-x-3">
                                                    {{-- مشاهده --}}
                                                    <a href="{{ route('sales.proformas.show', $proforma->id) }}" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                        مشاهده
                                                    </a>

                                                    {{-- ویرایش: فقط در پیش‌نویس --}}
                                                    @if($canEdit)
                                                        <a href="{{ route('sales.proformas.edit', $proforma->id) }}" 
                                                        class="text-indigo-600 hover:text-indigo-900">
                                                            ویرایش
                                                        </a>
                                                    @else
                                                        <button type="button"
                                                                onclick="showEditDeleteAlert('ویرایش فقط در وضعیت «پیش‌نویس» مجاز است.')"
                                                                class="text-gray-500 cursor-not-allowed opacity-60">
                                                            ویرایش
                                                        </button>
                                                    @endif

                                                    <!-- {{-- حذف: فقط اگر طبق Policy مجاز باشد --}}
                                                    @can('delete', $proforma)
                                                        <button type="submit"
                                                                form="single-delete-form"
                                                                formaction="{{ route('sales.proformas.destroy', $proforma->id) }}"
                                                                class="text-red-600 hover:underline"
                                                                onclick="return confirm('آیا از حذف این پیش‌فاکتور مطمئن هستید؟')">
                                                            حذف
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                                onclick="showEditDeleteAlert('شما مجاز به حذف این پیش‌فاکتور نیستید.')"
                                                                class="text-gray-500 cursor-not-allowed opacity-60">
                                                            حذف
                                                        </button>
                                                    @endcan -->
                                                </div>
                                            </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </form>

                    <div class="mt-4">
                        {{ $proformas->links() }}
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
    const rowChecks  = Array.from(document.querySelectorAll('.row-check'));
    const countBadge = document.getElementById('selected-count-badge');

    function refresh() {
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

    if (selectAll) {
        selectAll.addEventListener('change', e => {
            const v = e.target.checked;
            rowChecks.forEach(c => { if (!c.disabled) c.checked = v; });
            refresh();
        });
    }

    rowChecks.forEach(c => c.addEventListener('change', refresh));

    // هندل ارسال فرم (global)
    window.handleBulkDeleteSubmit = function (e) {
        const selected = rowChecks.filter(c => c.checked);
        if (selected.length === 0) {
            e.preventDefault();
            if (window.Swal) {
                Swal.fire({ icon: 'info', text: 'هیچ موردی انتخاب نشده است.' });
            } else {
                alert('هیچ موردی انتخاب نشده است.');
            }
            return false;
        }
        if (window.Swal) {
            e.preventDefault();
            Swal.fire({
                title: 'حذف گروهی',
                text: `آیا از حذف ${selected.length} مورد انتخابی اطمینان دارید؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف'
            }).then(res => { if (res.isConfirmed) form.submit(); });
            return false;
        }
        return true;
    };

    // مقدار اولیه
    refresh();
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

