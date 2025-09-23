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
                }
            @endphp

            {{-- Organization live filter (سبکِ مدال) --}}
            <div class="relative w-64">
                <input
                    type="text"
                    id="org-filter-input"
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

@endsection
