// resources/js/plugins/jdp-global.js
import $ from 'jquery';

// کمک‌تابع: از یونیکسِ انتخاب‌شده، میلادیِ YYYY-MM-DD در «۱۲ ظهر» بساز
function toGregorianYMDNoon(unixTs) {
  // persianDate از قبل توسط app.js لود شده
  const pd = new persianDate(unixTs).hour(12).minute(0).second(0);
  const g  = pd.toCalendar('gregorian');
  return g.format('YYYY-MM-DD');
}

// کمک‌تابع: از میلادیِ YYYY-MM-DD «نمایش شمسی» بساز (هم در ۱۲ ظهر)
function toPersianDisplayFromGregorian(ymd) {
  const [y, m, d] = ymd.split('-').map(Number);
  const pd = new persianDate([y, m, d]).hour(12).minute(0).second(0)
              .toCalendar('persian');
  return pd.format('YYYY/MM/DD');
}

$(function initGlobalJdp() {
  const $inputs = $('[data-jdp]');
  if (!$inputs.length) return;

  $inputs.each(function () {
    const $display = $(this);               // input شمسیِ نمایشی
    const name     = $display.attr('name') || '';
    // قاعده: hidden هم‌نام، بدون پسوند _shamsi
    const hiddenName = name.endsWith('_shamsi') ? name.replace(/_shamsi$/, '') : ( $display.data('target') || name );
    let $hidden = $(`input[name="${hiddenName}"]`);
    if (!$hidden.length) {
      $hidden = $('<input>', { type: 'hidden', name: hiddenName }).insertAfter($display);
    }

    // اگر مقدار میلادی از سرور آمده، ورودی شمسی را با همان مقدار سینک کن (در ۱۲ ظهر)
    const initialGregorian = ($hidden.val() || '').trim();
    if (initialGregorian) {
      $display.val(toPersianDisplayFromGregorian(initialGregorian));
    }

    // نصب datepicker
    $display.persianDatepicker({
      initialValue: false,
      format: 'YYYY/MM/DD',
      autoClose: true,
      // فقط انتخاب تاریخ؛ بدون زمان
      timePicker: { enabled: false },
      // هر بار انتخاب، hidden میلادی را با «۱۲ ظهر» به‌روزرسانی کن
      onSelect: function (unix) {
        $hidden.val(toGregorianYMDNoon(unix));
      }
    });
  });
});
