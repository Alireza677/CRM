@extends('layouts.app')
@php
    // بردکرامب صفحه ایجاد
    $breadcrumb = [
        ['title' => 'سرنخ‌های فروش', 'url' => route('marketing.leads.index')],
        ['title' => 'ایجاد سرنخ'],
    ];
@endphp
@section('content')
<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">ایجاد سرنخ جدید</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('marketing.leads.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @php if (!isset($lead)) $lead = new \App\Models\SalesLead(); @endphp
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                        @include('marketing.leads.partials.form-fields')
                    </div>
                    <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                        <a href="{{ route('marketing.leads.index') }}" class="btn btn-secondary">انصراف</a>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
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
  const statusEl = document.getElementById('lead_status');
  const dateTextEl = document.getElementById('next_followup_date');
  const dateHiddenEl = document.getElementById('next_follow_up_date');

  function toggleFollowupForLead() {
    const val = (statusEl && statusEl.value ? statusEl.value : '').toLowerCase();
    const isJunk = val === 'lost'; // 'سرکاری'
    if (isJunk) {
      if (dateTextEl) {
        dateTextEl.setAttribute('disabled', 'disabled');
        dateTextEl.classList.add('bg-gray-100', 'cursor-not-allowed');
      }
      if (dateHiddenEl) { dateHiddenEl.value = ''; }
    } else {
      if (dateTextEl) {
        dateTextEl.removeAttribute('disabled');
        dateTextEl.classList.remove('bg-gray-100', 'cursor-not-allowed');
      }
    }
  }

  document.addEventListener('DOMContentLoaded', toggleFollowupForLead);
  if (statusEl) statusEl.addEventListener('change', toggleFollowupForLead);
})();
</script>
@endpush


