@extends('layouts.app')

@section('title', 'مدیریت رویدادهای شرکت')

@section('content')
  @php
    $breadcrumb = [
      ['title' => 'مدیریت رویداد']
    ];
  @endphp
  <div class="max-w-4xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">مدیریت رویدادهای شرکت</h1>
              <span class="text-gray-500 text-sm mr-2">  با ثبت رویداد جدید علاوه بر نمایش مناسبت در تقویم میتوانید برای کاربران پیامک نیز ارسال کنید.</span>

    @if (session('status'))
      <div class="mb-4 rounded bg-green-50 text-green-700 px-3 py-2">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-md shadow p-4 mb-8">
      <h2 class="font-semibold mb-3">ثبت رویداد جدید</h2>
      <form id="holidayForm" action="{{ route('holidays.store') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">تاریخ</label>
            <input type="hidden" id="date" name="date" value="{{ old('date') }}">
            <input type="text"
                   id="date_shamsi"
                   class="persian-datepicker w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   data-alt-field="date"
                   placeholder="YYYY/MM/DD"
                   autocomplete="off">
            @error('date')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div>
            <label class="block text-sm mb-1">عنوان (اختیاری)</label>
            <input name="title" type="text" placeholder="پیش‌فرض: شرکت"
                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   value="{{ old('title') }}">
            @error('title')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="flex items-center gap-2 mt-6 md:mt-0">
            <input id="notify" name="notify" type="checkbox" value="1" class="rounded" {{ old('notify') ? 'checked' : '' }}>
            <label for="notify">ارسال اعلان (SMS)  </label>
          </div>
        </div>
        <div id="notify_message_wrapper" class="md:col-span-3 {{ old('notify') ? '' : 'hidden' }}">
          <label class="block text-sm mb-1">متن پیامک (اختیاری)</label>
          <textarea id="notify_message" name="notify_message" rows="3" placeholder="متن پیامکی که بعداً ارسال می‌شود"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('notify_message') }}</textarea>
          @error('notify_message')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
          @enderror
        </div>
        <!-- Signature field (appended to SMS text) -->
        <div id="notify_signature_wrapper" class="md:col-span-3 {{ old('notify') ? '' : 'hidden' }}">
          <label class="block text-sm mb-1">امضا (اختیاری)</label>
          <input id="notify_signature" name="notify_signature" type="text" placeholder="نام شرکت یا فرستنده"
                 class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                 value="{{ old('notify_signature') }}">
          <div class="text-xs text-gray-500 mt-1">این متن در انتهای پیامک اضافه می‌شود.</div>
          @error('notify_signature')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
          @enderror
        </div>
        
        <!-- Recipients selector (shown only when notify is checked) -->
        <div id="notify_recipients_wrapper" class="md:col-span-3 {{ old('notify') ? '' : 'hidden' }}">
          <div class="flex items-center gap-3">
            <button type="button" id="openUserModal" class="px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 border">انتخاب گیرندگان پیامک</button>
            <div id="selectedUsersSummary" class="text-sm text-gray-600">هیچ گیرنده‌ای انتخاب نشده است.</div>
          </div>
        </div>
        <div class="pt-2">
          <button class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">ثبت</button>
          
        </div>
        
        <!-- Users Modal -->
        <div id="userModal" class="fixed inset-0 bg-black/40 z-40 hidden">
          <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white w-full max-w-3xl rounded-md shadow-lg z-50">
              <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="font-semibold">انتخاب کاربران سیستم</h3>
                <button type="button" id="closeUserModal" class="text-gray-500 hover:text-gray-700" aria-label="بستن">✕</button>
              </div>
              <div class="p-4 overflow-x-auto max-h-[60vh] overflow-y-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="text-right px-3 py-2 w-10">
                        <!-- checkbox column header -->
                      </th>
                      <th class="text-right px-3 py-2">نام کاربر</th>
                      <th class="text-right px-3 py-2">شماره موبایل</th>
                      <th class="text-right px-3 py-2">نقش</th>
                    </tr>
                  </thead>
                  <tbody>
                  @php
                    $userList = isset($users) ? $users : collect();
                  @endphp
                  @if($userList && count($userList))
                  @foreach($userList as $u)
                    <tr class="border-t">
                      <td class="px-3 py-2 align-top">
                        <input type="checkbox"
                               class="user-checkbox rounded"
                               name="notify_user_ids[]"
                               value="{{ $u->id }}"
                               @if(in_array($u->id, old('notify_user_ids', []))) checked @endif
                               data-name="{{ $u->name ?? ('کاربر #' . $u->id) }}">
                      </td>
                      <td class="px-3 py-2">{{ $u->name ?? '—' }}</td>
                      <td class="px-3 py-2">{{ $u->mobile ?? ($u->phone ?? '—') }}</td>
                      <td class="px-3 py-2">
                        @php
                          $roleLabel = method_exists($u, 'getRoleNames')
                            ? $u->getRoleNames()->implode(', ')
                            : ($u->role_name ?? ($u->role ?? (isset($u->roles) ? (is_array($u->roles) ? implode(', ', $u->roles) : (string) $u->roles) : '—')));
                        @endphp
                        {{ $roleLabel }}
                      </td>
                    </tr>
                  @endforeach
                  @else
                    <tr>
                      <td colspan="4" class="px-3 py-6 text-center text-gray-500">کاربری برای نمایش وجود ندارد.</td>
                    </tr>
                  @endif
                  </tbody>
                </table>
              </div>
              <div class="flex items-center justify-between px-4 py-3 border-t">
                <div class="flex items-center gap-2">
                  <input id="selectAllUsers" type="checkbox" class="rounded">
                  <label for="selectAllUsers" class="text-sm">انتخاب همه</label>
                </div>
                <div class="flex items-center gap-2">
                  <button type="button" id="confirmUserSelection" class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">تایید انتخاب</button>
                  <button type="button" id="closeUserModal2" class="px-3 py-2 rounded-md border hover:bg-gray-50">بستن</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-md shadow">
      <div class="p-4 border-b font-semibold">لیست تعطیلات</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-right px-3 py-2">تاریخ</th>
              <th class="text-right px-3 py-2">عنوان</th>
              <th class="text-right px-3 py-2">ارسال اعلان</th>
              <th class="text-left px-3 py-2">اقدامات</th>
            </tr>
          </thead>
          <tbody>
          @forelse ($holidays as $h)
            <tr class="border-t">
              <td class="px-3 py-2">{{ optional($h->date)->format('Y-m-d') }}</td>
              <td class="px-3 py-2">{{ $h->title ?: 'تعطیلی شرکت' }}</td>
              <td class="px-3 py-2">{{ $h->notify ? 'بله' : 'خیر' }}</td>
              <td class="px-3 py-2 text-left flex items-center gap-3">
                <a href="{{ route('holidays.show', $h) }}" class="text-gray-600 hover:text-gray-800" title="مشاهده پیامک‌ها">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.01 9.964 7.178.07.207.07.437 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.01-9.964-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </a>
                <a href="{{ route('holidays.edit', $h) }}" class="text-blue-600 hover:underline">ویرایش</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">موردی ثبت نشده است</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="p-3">{{ $holidays->links() }}</div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var notifyCheckbox   = document.getElementById('notify');
    var messageWrapper   = document.getElementById('notify_message_wrapper');
    var recipientsWrapper= document.getElementById('notify_recipients_wrapper');
    var signatureWrapper = document.getElementById('notify_signature_wrapper');
    var signatureInput   = document.getElementById('notify_signature');
    var messageInput     = document.getElementById('notify_message');
    var formEl           = document.getElementById('holidayForm');

    function toggleNotifySections() {
      if (!notifyCheckbox) return;
      var show = !!notifyCheckbox.checked;
      if (messageWrapper)   messageWrapper.classList.toggle('hidden', !show);
      if (recipientsWrapper)recipientsWrapper.classList.toggle('hidden', !show);
      if (signatureWrapper) signatureWrapper.classList.toggle('hidden', !show);
    }

    toggleNotifySections();
    if (notifyCheckbox) notifyCheckbox.addEventListener('change', toggleNotifySections);

    // Modal controls
    var openBtn   = document.getElementById('openUserModal');
    var modal     = document.getElementById('userModal');
    var closeBtn  = document.getElementById('closeUserModal');
    var closeBtn2 = document.getElementById('closeUserModal2');
    var confirmBtn= document.getElementById('confirmUserSelection');
    var summaryEl = document.getElementById('selectedUsersSummary');
    var selectAll = document.getElementById('selectAllUsers');

    function openModal()  { if (modal) modal.classList.remove('hidden'); }
    function closeModal() { if (modal) modal.classList.add('hidden'); }

    if (openBtn)  openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (closeBtn2)closeBtn2.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });

    function getCheckedUsers() {
      var boxes = document.querySelectorAll('.user-checkbox');
      var selected = [];
      boxes.forEach(function (b) {
        if (b.checked) {
          selected.push({ id: b.value, name: b.getAttribute('data-name') || ('کاربر #' + b.value) });
        }
      });
      return selected;
    }

    function updateSummary() {
      if (!summaryEl) return;
      var selected = getCheckedUsers();
      if (selected.length === 0) {
        summaryEl.textContent = 'هیچ گیرنده‌ای انتخاب نشده است.';
        return;
      }
      var names = selected.slice(0, 3).map(function (u) { return u.name; }).join('، ');
      var extra = selected.length > 3 ? (' و ' + (selected.length - 3) + ' نفر دیگر') : '';
      summaryEl.textContent = selected.length + ' نفر انتخاب شده: ' + names + extra;
    }

    if (confirmBtn) confirmBtn.addEventListener('click', function () {
      updateSummary();
      closeModal();
    });

    // Live update when ticking checkboxes
    document.querySelectorAll('.user-checkbox').forEach(function (el) {
      el.addEventListener('change', updateSummary);
    });

    if (selectAll) selectAll.addEventListener('change', function () {
      var boxes = document.querySelectorAll('.user-checkbox');
      boxes.forEach(function (b) { b.checked = !!selectAll.checked; });
      updateSummary();
    });

    // Initialize summary on load (handles old() pre-checked items)
    updateSummary();

    // On submit, append signature to message if present
    if (formEl) {
      formEl.addEventListener('submit', function () {
        try {
          if (!notifyCheckbox || !notifyCheckbox.checked) return;
          if (!messageInput || !signatureInput) return;
          var sig = (signatureInput.value || '').trim();
          if (!sig) return;
          var msg = messageInput.value || '';
          // Append signature only if it doesn't already end with it
          var endsWithSig = msg.trim().endsWith(sig);
          if (!endsWithSig) {
            messageInput.value = msg ? (msg + '\n' + sig) : sig;
          }
        } catch (e) {
          // no-op
        }
      });
    }
  });
  </script>
@endpush
