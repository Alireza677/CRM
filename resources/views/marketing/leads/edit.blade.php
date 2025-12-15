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
                    <input type="hidden" name="disqual_reason_body" id="lead_disqual_reason_body" value="{{ old('disqual_reason_body', '') }}">
                    <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                        <a href="{{ route('marketing.leads.show', $lead) }}" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="leadDisqualifyModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">دلیل سرکاری شدن سرنخ</h3>
            <button type="button" class="text-2xl text-gray-500 hover:text-gray-700" id="leadDisqualifyClose">&times;</button>
        </div>
        <p class="text-sm text-gray-600 mb-3">برای ثبت وضعیت سرکاری، لطفاً دلیل دقیق و شفاف را وارد کنید تا در پرونده سرنخ ثبت شود و تیم بتواند تصمیم را پیگیری کند.</p>
        <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 mb-2">دلایل رایج سرکاری شدن</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                    <input type="checkbox" name="disqual_reasons[]" value="عدم نیاز واقعی" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>عدم نیاز واقعی</span>
                </label>
                <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                    <input type="checkbox" name="disqual_reasons[]" value="عدم بودجه" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>عدم بودجه</span>
                </label>
                <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                    <input type="checkbox" name="disqual_reasons[]" value="مخاطب تصمیم گیرنده نیست" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>تصمیم گیرنده نیست</span>
                </label>
                <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                    <input type="checkbox" name="disqual_reasons[]" value="قیمت رقبا" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>قیمت رقبا</span>
                </label>
                <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                    <input type="checkbox" name="disqual_reasons[]" value="اطلاعات اشتباه یا تکراری بود" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>اطلاعات اشتباه یا تکراری بود</span>
                </label>
                <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                    <input type="checkbox" name="disqual_reasons[]" value="خارج از حوزه فعالیت شرکت" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>خارج از حوزه فعالیت شرکت</span>
                </label>
            </div>
        </div>
        <textarea id="leadDisqualifyReason" rows="4" class="w-full rounded-md border border-gray-300 p-3 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-200" placeholder="دلیل وضعیت سرکاری را بنویسید…">{{ old('disqual_reason_body', '') }}</textarea>
        <p id="leadDisqualifyError" class="mt-2 text-sm text-red-600 hidden">لطفاً دلیل وضعیت سرکاری را وارد کنید.</p>
        <div class="mt-5 flex justify-end space-x-3 space-x-reverse">
            <button type="button" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300" id="leadDisqualifyCancel">انصراف</button>
            <button type="button" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" id="leadDisqualifySubmit">ثبت دلیل وضعیت سرکاری</button>
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
  const disqualModal = document.getElementById('leadDisqualifyModal');
  const disqualTextarea = document.getElementById('leadDisqualifyReason');
  const disqualError = document.getElementById('leadDisqualifyError');
  const disqualHidden = document.getElementById('lead_disqual_reason_body');
  const disqualChecks = document.querySelectorAll('input[name="disqual_reasons[]"]');
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
    if (current === 'junk') return false;
    return current !== originalStatus;
  }

  function isJunkStatus() {
    return (statusSelect?.value || '').toLowerCase() === 'junk';
  }

  function openDisqualModal() {
    if (!disqualModal) return;
    if (disqualTextarea && disqualHidden?.value && !disqualTextarea.value) {
      disqualTextarea.value = disqualHidden.value;
    }
    disqualModal.classList.remove('hidden');
    disqualModal.classList.add('flex');
    if (disqualError) disqualError.classList.add('hidden');
    setTimeout(() => disqualTextarea?.focus(), 10);
  }

  function closeDisqualModal() {
    if (!disqualModal) return;
    disqualModal.classList.add('hidden');
    disqualModal.classList.remove('flex');
  }

  function buildDisqualNote() {
    const selectedReasons = Array.from(disqualChecks || [])
      .filter(c => c.checked)
      .map(c => c.value)
      .filter(Boolean);
    const reasonsText = selectedReasons.length ? ` دلایل رد صلاحیت سرنخ:  ${selectedReasons.join('، ')}` : '';
    const freeText = (disqualTextarea?.value || '').trim();
    if (reasonsText && freeText) {
      return `${reasonsText}\n${freeText}`;
    }
    return (reasonsText || freeText || '').trim();
  }

  function submitDisqualReason() {
    if (!isJunkStatus()) return;
    const reason = buildDisqualNote();
    if (!reason) {
      if (disqualError) disqualError.classList.remove('hidden');
      return;
    }
    if (disqualHidden) disqualHidden.value = reason;
    if (quickNoteHidden) quickNoteHidden.value = reason;
    if (overrideInput) overrideInput.value = '1';
    closeDisqualModal();
    if (form?.requestSubmit) { form.requestSubmit(); } else { form?.submit(); }
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
      if (isJunkStatus()) {
        const hasCheckboxReason = Array.from(disqualChecks || []).some(c => c.checked);
        const reasonFilled = hasCheckboxReason || (disqualTextarea?.value || '').trim() !== '' || (disqualHidden?.value || '').trim() !== '';
        if (!reasonFilled) {
          e.preventDefault();
          openDisqualModal();
          return;
        }
        const combined = buildDisqualNote();
        if (disqualHidden && (disqualHidden.value === '' || combined)) {
          disqualHidden.value = combined;
        }
        if (quickNoteHidden && (quickNoteHidden.value === '' || combined)) {
          quickNoteHidden.value = combined;
        }
        if (overrideInput) overrideInput.value = '1';
      }
      if (!shouldRequireNote()) return;
      e.preventDefault();
      openModal();
    });
  }

  document.getElementById('leadActivitySubmit')?.addEventListener('click', submitWithNote);
  document.getElementById('leadActivityCancel')?.addEventListener('click', closeModal);
  document.getElementById('leadActivityClose')?.addEventListener('click', closeModal);
  document.getElementById('leadDisqualifySubmit')?.addEventListener('click', submitDisqualReason);
  document.getElementById('leadDisqualifyCancel')?.addEventListener('click', closeDisqualModal);
  document.getElementById('leadDisqualifyClose')?.addEventListener('click', closeDisqualModal);
  statusSelect?.addEventListener('change', function(){
    if (isJunkStatus()) {
      openDisqualModal();
    } else {
      closeDisqualModal();
      if (disqualHidden && disqualHidden.value !== '') {
        if (overrideInput) overrideInput.value = '0';
        if (quickNoteHidden && quickNoteHidden.value === disqualHidden.value) {
          quickNoteHidden.value = '';
        }
      }
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModal();
      closeDisqualModal();
    }
  });
  modal?.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
  disqualModal?.addEventListener('click', function (e) {
    if (e.target === disqualModal) closeDisqualModal();
  });
})();
</script>
@endpush




