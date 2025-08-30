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
    <x-header />
    <x-sidebar />

    <x-breadcrumb :items="$breadcrumb ?? []" />

    <main class="mx-[40px] transition-all duration-300">
    @yield('content')
    </main>
</div>

<!-- Persian Datepicker Dependencies (Local) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-date.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-datepicker.min.js') }}"></script>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Datepicker Config -->
<script>
    $(function () {
        // تاریخ پیگیری بعدی (مثلاً در فرم فرصت فروش)
        if ($('#next_follow_up_shamsi').length) {
            $('#next_follow_up_shamsi').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                onSelect: function (unix) {
                    const gDate = new persianDate(unix).toLocale('en').format('YYYY-MM-DD');
                    $('#next_follow_up').val(gDate);
                }
            });
        }

        // تاریخ پیش‌فاکتور (در صفحه ایجاد پیش‌فاکتور)
        if ($('#proforma_date_shamsi').length) {
            $('#proforma_date_shamsi').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                onSelect: function (unix) {
                    const gDate = new persianDate(unix).toLocale('en').format('YYYY-MM-DD');
                    $('#proforma_date').val(gDate);
                }
            });
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


@stack('scripts')
</body>
</html>
