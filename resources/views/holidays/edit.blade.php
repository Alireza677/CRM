@extends('layouts.app')

@section('title', 'ویرایش تعطیلی')

@section('content')
  <div class="max-w-3xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">ویرایش تعطیلی</h1>

    <div class="bg-white rounded-md shadow p-4">
      <form action="{{ route('holidays.update', $holiday) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">از تاریخ</label>
            <input type="hidden" id="date" name="date" value="{{ old('date', optional($holiday->date)->format('Y-m-d')) }}">
            <input type="text"
                   id="date_shamsi"
                   class="persian-datepicker w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   data-alt-field="date"
                   placeholder="YYYY/MM/DD"
                   autocomplete="off"
                   required>
            @error('date')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div>
            <label class="block text-sm mb-1">تا تاریخ</label>
            <input type="hidden" id="date_end" name="date_end" value="{{ old('date_end', optional($holiday->date_end ?? $holiday->date)->format('Y-m-d')) }}">
            <input type="text"
                   id="date_end_shamsi"
                   class="persian-datepicker w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   data-alt-field="date_end"
                   placeholder="YYYY/MM/DD"
                   autocomplete="off">
            @error('date_end')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div>
            <label class="block text-sm mb-1">عنوان (اختیاری)</label>
            <input name="title" type="text" placeholder="پیش‌فرض: تعطیلی شرکت"
                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                   value="{{ old('title', $holiday->title) }}">
            @error('title')
              <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <!-- Previous SMS message (read-only display) -->
        <div class="md:col-span-3">
          <label class="block text-sm mb-1">آخرین متن پیامک ثبت‌شده</label>
          @if ($holiday->notify_message)
            <div class="whitespace-pre-wrap rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">{{ $holiday->notify_message }}</div>
          @else
            <div class="rounded-md border border-dashed border-gray-200 p-3 text-sm text-gray-500">متن پیامک قبلی ثبت نشده است.</div>
          @endif
        </div>
        <!-- Resend SMS section -->
        <div class="border-t pt-4 mt-2">
          <div class="flex items-center gap-2">
            <input id="resend_sms" name="resend_sms" type="checkbox" value="1" class="rounded" {{ old('resend_sms') ? 'checked' : '' }}>
            <label for="resend_sms" class="font-medium">ارسال مجدد پیامک همین الان</label>
            @if ($holiday->notify_sent_at)
              <span class="text-xs text-gray-500 mr-2">اولین ارسال: {{ $holiday->notify_sent_at }}</span>
            @endif
          </div>
          <div id="resend_message_wrapper" class="mt-3 {{ old('resend_sms') ? '' : 'hidden' }}">
            <label class="block text-sm mb-1">متن پیامک</label>
            <textarea name="notify_message" rows="3" placeholder="متن پیامک"
                      class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('notify_message', $holiday->notify_message) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">اگر خالی بماند، متن پیش‌فرض بر اساس تاریخ و عنوان ساخته می‌شود.</p>
          </div>
          <div id="resend_recipients_wrapper" class="mt-3 {{ old('resend_sms') ? '' : 'hidden' }}">
            <div class="flex items-center gap-3">
              <button type="button" id="openUserModal" class="px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 border">انتخاب گیرندگان پیامک</button>
              <div id="selectedUsersSummary" class="text-sm text-gray-600">هیچ گیرنده‌ای انتخاب نشده است.</div>
            </div>
          </div>
        </div>

        <div class="pt-2">
          <button class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">ذخیره</button>
          <a href="{{ route('holidays.index') }}" class="px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 mr-2">بازگشت</a>
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
                      <th class="text-right px-3 py-2 w-10"></th>
                      <th class="text-right px-3 py-2">نام کاربر</th>
                      <th class="text-right px-3 py-2">شماره موبایل</th>
                      <th class="text-right px-3 py-2">نقش</th>
                    </tr>
                  </thead>
                  <tbody>
                  @php $userList = isset($users) ? $users : collect(); @endphp
                  @if($userList && count($userList))
                  @foreach($userList as $u)
                    <tr class="border-t">
                      <td class="px-3 py-2 align-top">
                        <input type="checkbox"
                               class="user-checkbox rounded"
                               name="notify_user_ids[]"
                               value="{{ $u->id }}"
                               @if(in_array($u->id, old('notify_user_ids', []))) checked @endif
                               data-name="{{ $u->name ?? ('کاربر #'.$u->id) }}">
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
  </div>
@endsection

@push('scripts')
<script>
  // Resend toggle + modal behavior
  document.addEventListener('DOMContentLoaded', function () {
    var resendCheckbox   = document.getElementById('resend_sms');
    var msgWrapper       = document.getElementById('resend_message_wrapper');
    var recipientsWrapper= document.getElementById('resend_recipients_wrapper');

    function toggleResendSections() {
      var show = !!(resendCheckbox && resendCheckbox.checked);
      if (msgWrapper)        msgWrapper.classList.toggle('hidden', !show);
      if (recipientsWrapper) recipientsWrapper.classList.toggle('hidden', !show);
    }
    toggleResendSections();
    if (resendCheckbox) resendCheckbox.addEventListener('change', toggleResendSections);

    // Modal controls (reuse IDs like index)
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
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    function getCheckedUsers() {
      var boxes = document.querySelectorAll('.user-checkbox');
      var selected = [];
      boxes.forEach(function (b) {
        if (b.checked) selected.push({ id: b.value, name: b.getAttribute('data-name') || ('کاربر #' + b.value) });
      });
      return selected;
    }

    function updateSummary() {
      if (!summaryEl) return;
      var selected = getCheckedUsers();
      if (selected.length === 0) { summaryEl.textContent = 'هیچ گیرنده‌ای انتخاب نشده است.'; return; }
      var names = selected.slice(0, 3).map(function (u) { return u.name; }).join('، ');
      var extra = selected.length > 3 ? (' و ' + (selected.length - 3) + ' نفر دیگر') : '';
      summaryEl.textContent = selected.length + ' نفر انتخاب شده: ' + names + extra;
    }

    if (confirmBtn) confirmBtn.addEventListener('click', function () { updateSummary(); closeModal(); });
    document.querySelectorAll('.user-checkbox').forEach(function (el) { el.addEventListener('change', updateSummary); });
    if (selectAll) selectAll.addEventListener('change', function () {
      var boxes = document.querySelectorAll('.user-checkbox');
      boxes.forEach(function (b) { b.checked = !!selectAll.checked; });
      updateSummary();
    });
    updateSummary();
  });
</script>
@endpush
