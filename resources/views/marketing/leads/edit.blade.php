@extends('layouts.app')
@section('content')
<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">ویرایش سرنخ</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('marketing.leads.update', $lead) }}" method="POST" class="space-y-6" id="leadForm">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1  gap-6">
                        @include('marketing.leads.partials.form-fields', ['lead' => $lead])
                    </div>
                    <input type="hidden" name="activity_override" id="lead_activity_override" value="{{ old('activity_override', 0) }}">
                    <input type="hidden" name="quick_note_body" id="lead_quick_note_body" value="{{ old('quick_note_body', '') }}">
                    <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                        <a href="{{ route('marketing.leads.show', $lead) }}" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    </div>
                </form>
                @include('marketing.leads.partials.contact-modal')
            </div>
        </div>
    </div>
</div>

<div id="leadActivityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">ثبت فعالیت قبل از تغییر وضعیت</h3>
            <button type="button" class="text-2xl text-gray-500 hover:text-gray-700" id="leadActivityClose">&times;</button>
        </div>
        <p class="text-sm text-gray-600 mb-3">برای تغییر وضعیت این سرنخ ابتدا یک یادداشت کوتاه ثبت کنید.</p>
        <textarea id="leadActivityNote" rows="4" class="w-full rounded-md border border-gray-300 p-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="یادداشت سریع..."></textarea>
        <p id="leadActivityError" class="mt-2 text-sm text-red-600 hidden">لطفاً متن یادداشت را وارد کنید.</p>
        <div class="mt-5 flex justify-end space-x-3 space-x-reverse">
            <button type="button" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300" id="leadActivityCancel">انصراف</button>
            <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" id="leadActivitySubmit">ثبت یادداشت و ادامه</button>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
$(function () {
  function toEnDigits(s){
    if (s == null) return s;
    return String(s)
      .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
      .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
  }

  $('.persian-datepicker').each(function () {
    const $ui   = $(this);
    const altId = $ui.data('alt-field');
    const $alt  = altId ? $('#' + altId) : $();

    // اگر hidden میلادی داشت و UI خالی بود، UI را شمسی کن (با ورودی Gregorian صریح)
    const gDateVal = altId ? toEnDigits($alt.val()) : '';
    if (altId && gDateVal && !$ui.val()) {
      try {
        const [gy, gm, gd] = gDateVal.split('-').map(Number);
        const j = new persianDate([gy, gm, gd])
          .calendar('gregorian')
          .toCalendar('persian');
        $ui.val(j.format('YYYY/MM/DD'));
      } catch (e) { console.warn('init sync error', e); }
    }

    // راه‌اندازی دیت‌پیکر با تقویم رسمی ایران
    $ui.persianDatepicker({
      format: 'YYYY/MM/DD',
      initialValueType: 'persian',
      initialValue: true,
      autoClose: true,
      observer: true,
      calendar: {
        persian:   { locale: 'fa', leapYearMode: 'astronomical' }, // مهم
        gregorian: { locale: 'en' }
      },
      altField: altId ? '#' + altId : undefined, // برای اتصال خودکار
      altFormat: 'YYYY-MM-DD',
      onSelect: function (unix) {
        // مقدار hidden را حتماً میلادیِ لاتین ذخیره کن
        if (!altId) return;
        const g = new persianDate(unix)
          .toCalendar('gregorian')
          .toLocale('en')
          .format('YYYY-MM-DD');
        $('#' + altId).val(g);
      }
    });

    // اگر کاربر تایپ/پاک کرد، hidden را دوباره میلادی کن
    $ui.on('input blur', function(){
      if (!altId) return;
      const raw = (toEnDigits($ui.val()||'')).trim();
      const m = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
      if (!m) return;
      const g = new persianDate([+m[1], +m[2], +m[3]])
        .calendar('persian')
        .toCalendar('gregorian')
        .toLocale('en')
        .format('YYYY-MM-DD');
      $('#' + altId).val(g);
    });
  });
});
</script>
@endpush

@push('scripts')
<script>
(function () {
  const form = document.getElementById('leadEditForm');
  const modal = document.getElementById('leadActivityModal');
  const noteInput = document.getElementById('leadActivityNote');
  const errorEl = document.getElementById('leadActivityError');
  const statusSelect = document.getElementById('lead_status');
  const overrideInput = document.getElementById('lead_activity_override');
  const quickNoteHidden = document.getElementById('lead_quick_note_body');
  const hasRecentActivity = Boolean(@json($hasRecentActivity ?? false));
  const originalStatus = (statusSelect ? (statusSelect.dataset.originalValue || '') : '').toLowerCase();

  function openModal() {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (errorEl) errorEl.classList.add('hidden');
    setTimeout(() => noteInput?.focus(), 10);
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function shouldRequireNote() {
    if (!form || !statusSelect) return false;
    if (overrideInput && overrideInput.value === '1') return false;
    if (hasRecentActivity) return false;
    const current = (statusSelect.value || '').toLowerCase();
    if (current === 'discarded') return false;
    return current !== originalStatus;
  }

  function isJunkStatus() {
    return (statusSelect?.value || '').toLowerCase() === 'discarded';
  }

  function submitWithNote() {
    const note = (noteInput?.value || '').trim();
    if (!note) {
      if (errorEl) {
        errorEl.textContent = 'Please enter a note before continuing.';
        errorEl.classList.remove('hidden');
      }
      return;
    }
    if (quickNoteHidden) quickNoteHidden.value = note;
    if (overrideInput) overrideInput.value = '1';
    closeModal();
    if (form?.requestSubmit) { form.requestSubmit(); } else { form?.submit(); }
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      if (!shouldRequireNote()) return;
      e.preventDefault();
      openModal();
    });
  }

  document.getElementById('leadActivitySubmit')?.addEventListener('click', submitWithNote);
  document.getElementById('leadActivityCancel')?.addEventListener('click', closeModal);
  document.getElementById('leadActivityClose')?.addEventListener('click', closeModal);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModal();
    }
  });
  modal?.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
})();
</script>
@endpush

