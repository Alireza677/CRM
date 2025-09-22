@extends('layouts.app')
@php
$breadcrumb = [
        ['title' => 'سرنخ‌های فروش', 'url' => route('marketing.leads.index')],
        ['title' => 'ویرایش سرنخ']
    ];@endphp
@section('content')
<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">ویرایش سرنخ</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('marketing.leads.update', $lead) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1  gap-6">
                        @include('marketing.leads.partials.form-fields', ['lead' => $lead])
                    </div>
                    <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                        <a href="{{ route('marketing.leads.show', $lead) }}" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
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
