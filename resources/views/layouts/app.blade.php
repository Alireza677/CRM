<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap (RTL) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />

    <!-- Persian Datepicker (Local) -->
    <link href="{{ asset('vendor/persian-datepicker/css/persian-datepicker.min.css') }}" rel="stylesheet">

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Vite -->
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    @yield('styles')
    @stack('style')

    
    <style>
         [x-cloak] { display: none !important; }
        @font-face {
            font-family: 'IRANSans';
            src: url('{{ asset('fonts/iransans/IRANSans.woff2') }}') format('woff2'),
                url('{{ asset('fonts/iransans/IRANSans.woff') }}') format('woff');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        </style>

</head>

<body x-data="{ mainMenuOpen: false, subMenuOpen: false, activeMenu: null, openSubMenu(name) { this.subMenuOpen = true; this.activeMenu = name; } }"
      class="font-sans antialiased">
<div class="min-h-screen bg-gray-100">
    <x-header :breadcrumb="$breadcrumb ?? []" />
    <x-sidebar />

    <div class=" ">
        @yield('header')
    </div>
    
    <main class=" transition-all duration-300">
    @yield('content')
    </main>
</div>

<!-- Persian Datepicker Dependencies (Local) -->
<script src="{{ asset('vendor/persian-datepicker/js/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-date.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-datepicker.min.js') }}"></script>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Datepicker Config (fixed) -->
<script>
  $(function () {

    function toEnDigits(s){
      if (s == null) return s;
      return String(s)
        .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
        .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
    }

    function initPicker($input, altSel){
      if (!$input.length) return;

      // اگر قبلاً مقداردهی شده، نابودش کن تا با گزینه‌های جدید بسازیم
      try { $input.persianDatepicker('destroy'); } catch(e){}

      // اگر hidden میلادی از قبل مقدار داشت و UI خالی است، UI را شمسی کن
      if (altSel) {
        const gVal = toEnDigits($(altSel).val() || '');
        if (gVal && !$input.val()) {
          try {
            const [gy, gm, gd] = gVal.split('-').map(Number);
            const j = new persianDate([gy, gm, gd])
              .toCalendar('gregorian')
              .toCalendar('persian');
            $input.val(j.format('YYYY/MM/DD'));
          } catch(e){}
        }
      }

      // مقداردهی دیت‌پیکر با تقویم رسمی ایران
      $input.persianDatepicker({
        format: 'YYYY/MM/DD',
        initialValueType: 'persian',
        initialValue: !!$input.val(),
        autoClose: true,
        observer: true,
        calendar: {
          persian:   { locale: 'fa', leapYearMode: 'astronomical' },
          gregorian: { locale: 'en' }
        },
        altField: altSel || undefined,
        altFormat: 'YYYY-MM-DD',
        onSelect(unix){
          if (!altSel) return;
          const g = new persianDate(unix)
            .toCalendar('gregorian')
            .toLocale('en')
            .format('YYYY-MM-DD');
          $(altSel).val(g);
        }
      });

      // همگام‌سازی هنگام تایپ/پاک‌کردن دستی
      if (altSel) {
        $input.on('input blur', function(){
          const raw = toEnDigits(($input.val()||'').trim());
          const m = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
          if (!m) return;
          const g = new persianDate([+m[1], +m[2], +m[3]])
            .toCalendar('persian')
            .toCalendar('gregorian')
            .toLocale('en')
            .format('YYYY-MM-DD');
          $(altSel).val(g);
        });
      }
    }

    // فیلدهای مشخصی که در لایه مقداردهی می‌کردی:
    initPicker($('#next_follow_up_shamsi'), '#next_follow_up');
    initPicker($('#proforma_date_shamsi'), '#proforma_date');

    // Ensure default "today" is submitted on create
    // If both the visible Jalali field and its hidden alt are empty,
    // prefill them with today's date (Jalali for UI, Gregorian for server).
    if (!$('#proforma_date_shamsi').val() && !$('#proforma_date').val()) {
      const today = new persianDate();
      $('#proforma_date_shamsi')
        .val(today.toCalendar('persian').toLocale('fa').format('YYYY/MM/DD'));
      $('#proforma_date')
        .val(today.toCalendar('gregorian').toLocale('en').format('YYYY-MM-DD'));
    }

    // پشتیبانی عمومی برای هر input با کلاس persian-datepicker و data-alt-field / data-target
    $('.persian-datepicker').each(function(){
      const $i = $(this);
      // Read raw attribute to avoid jQuery camelCase data() mismatch
      const altId = $i.attr('data-alt-field') || $i.attr('data-target');
      if (altId) initPicker($i, '#' + altId); else initPicker($i, null);
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Flash popups -->
@if (session('success'))
<script>
  document.addEventListener('DOMContentLoaded', function(){
    if (window.Swal) {
      Swal.fire({
        icon: 'success',
        title: 'ثبت موفق',
        text: @json(session('success')),
        confirmButtonText: 'باشه',
        timer: 2500,
        timerProgressBar: true
      });
    }
  });
</script>
@endif

@if (session('error'))
<script>
  document.addEventListener('DOMContentLoaded', function(){
    if (window.Swal) {
      Swal.fire({
        icon: 'error',
        title: 'خطا در ذخیره',
        text: @json(session('error')),
        confirmButtonText: 'باشه'
      });
    }
  });
</script>
@endif


@stack('scripts')
</body>
</html>
