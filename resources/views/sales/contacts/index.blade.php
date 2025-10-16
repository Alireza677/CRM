@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [['title' => 'مخاطبین']];
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">مخاطبین</h2>
                <!-- Create, Import & Export Buttons -->
                <div class="mb-4 flex items-center gap-2">
                    <a href="{{ route('sales.contacts.create') }}"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-xs font-semibold">
                        <i class="fas fa-plus ml-1"></i> ایجاد مخاطب جدید
                    </a>
                    @role('admin')
                        <a href="{{ route('sales.contacts.import.form') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs font-semibold">
                            <i class="fas fa-arrow-down ml-1"></i> ایمپورت مخاطبین
                        </a>

                   
                        <a href="{{ route('sales.contacts.export.format', ['format' => 'csv']) }}"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-xs font-semibold">
                            <i class="fas fa-file-csv ml-1"></i> اکسپورت (CSV)
                        </a>

                        {{-- XLSX --}}
                        <a href="{{ route('sales.contacts.export.format', ['format' => 'xlsx']) }}"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-xs font-semibold">
                            <i class="fas fa-file-excel ml-1"></i> اکسپورت (XLSX)
                        </a>
                        @endrole
                </div>


        <!-- Search / Filter Form -->
        <form method="GET" action="{{ route('sales.contacts.index') }}" class="bg-white shadow-sm rounded p-4 mb-4 flex flex-wrap gap-4 items-end">

        <input type="text" name="search" placeholder="نام یا موبایل..." value="{{ request('search') }}"
                class="border rounded px-3 py-2 w-52">

            <select name="assigned_to" class="border rounded px-3 py-2 w-52">
                <option value="">همه ارجاع‌ها</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>

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

            {{-- Organization live filter (سبکِ مدال) --}}
            <div class="relative w-64">
                <input
                    type="text"
                    id="org-filter-input"
                    name="organization_name"
                    placeholder="جستجوی سازمان..."
                    class="border rounded px-3 py-2 w-full"
                    value="{{ $selectedOrgName }}"
                    autocomplete="off"
                />
                {{-- مقدار واقعی که ارسال می‌شود --}}
                <input type="hidden" name="organization" id="org-id-input" value="{{ $selectedOrgId }}"/>

                {{-- دکمه پاک‌سازی انتخاب --}}
                <button type="button" id="org-clear"
                        class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                    ×
                </button>

                {{-- لیست قابل جستجو --}}
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


            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">جستجو</button>
            <a href="{{ route('sales.contacts.index') }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">پاکسازی</a>
        </form>

        <!-- حذف گروهی -->
        <!-- Per page selector (outside filter form, preserves query via JS) -->
        <div class="flex items-center gap-2 mb-2">
            <label for="per-page-selector" class="text-sm text-gray-700">تعداد در صفحه</label>
            <select id="per-page-selector" class="border rounded px-3 py-2 w-28">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
        </div>

        <script>
        (function () {
            try {
                var sel = document.getElementById('per-page-selector');
                if (!sel) return;
                var params = new URLSearchParams(window.location.search);
                var current = parseInt(params.get('per_page') || '100', 10);
                if ([25,50,100,200].indexOf(current) === -1) current = 100;
                sel.value = String(current);
                sel.addEventListener('change', function () {
                    var p = new URLSearchParams(window.location.search);
                    p.set('per_page', this.value);
                    window.location.search = p.toString();
                });
            } catch (e) {}
        })();
        </script>

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
                            <tr>
                                <th class="px-4 py-3">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">موبایل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">سازمان</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ارجاع به</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ ایجاد</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($contacts as $contact)
                                <tr>
                                    <td class="px-4 py-4">
                                        <input type="checkbox" name="selected_contacts[]" value="{{ $contact->id }}" class="select-contact">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('sales.contacts.show', $contact->id) }}"
                                           class="text-sm font-medium text-blue-600 hover:underline">
                                           {{ $contact->first_name }} {{ $contact->last_name }}
                                        </a>
                                        @if($contact->is_favorite)
                                            <i class="fas fa-star text-yellow-400 ml-1"></i>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $contact->mobile }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $contact->organization_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $contact->assigned_to_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ jdate($contact->created_at)->format('Y/m/d H:i')}}</td>
                                    <td class="px-6 py-4 text-sm text-blue-600 flex items-center gap-2">
                                        <button type="button"
                                                class="text-indigo-600 hover:text-indigo-800"
                                                title="افزودن به لیست پیامک"
                                                onclick="openSmsListModal({{ $contact->id }}, '{{ addslashes(trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''))) }}')">
                                            <i class="fas fa-envelope ml-1"></i>
                                        </button>
                                        <a href="{{ route('sales.contacts.edit', $contact->id) }}" class="hover:underline">
                                            <i class="fas fa-edit ml-1"></i> ویرایش
                                        </a>
                                        {{-- فرم حذف تکی کاملاً جدا از فرم bulk-delete --}}
                                        <form method="POST" action="{{ route('sales.contacts.destroy', $contact->id) }}" onsubmit="return confirm('آیا از حذف این مخاطب مطمئن هستید؟');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline ml-2">
                                                <i class="fas fa-trash-alt ml-1"></i> حذف
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $contacts->links() }}
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

  if (!input || !hidden || !list) return;

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
  });

  // تایپ برای فیلتر
  input.addEventListener('input', function () {
    filterList(this.value);
    showList();
    // اگر کاربر تایپ کرد، انتخاب قبلی را باطل نکن—فقط موقع سابمیت مهم است.
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
  });

  // وضعیت اولیه
  filterList(input.value);
  updateClearVisibility();
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
