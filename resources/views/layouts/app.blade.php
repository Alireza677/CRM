<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="notifications-stream-url" content="{{ route('notifications.stream') }}">
    <meta name="notifications-unread-count-url" content="{{ route('notifications.unreadCount') }}">
    <meta name="notifications-feed-url" content="{{ route('notifications.feed.latest') }}">
    <meta name="notifications-asset-settings-url" content="{{ route('notifications.asset-settings') }}">
    <meta name="webpush-vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <meta name="webpush-subscribe-url" content="{{ route('webpush.subscribe') }}">
    <meta name="webpush-unsubscribe-url" content="{{ route('webpush.unsubscribe') }}">
    @php
        $apiAuthExpires = now()->addMinutes((int) config('app.api_auth_ttl_minutes', 720))->timestamp;
        $apiAuthKey = (string) config('app.key');
        if (\Illuminate\Support\Str::startsWith($apiAuthKey, 'base64:')) {
            $apiAuthKey = base64_decode(substr($apiAuthKey, 7)) ?: '';
        }
        $apiAuthSignature = hash_hmac('sha256', auth()->id() . '|' . $apiAuthExpires, $apiAuthKey);
    @endphp
    <meta name="api-auth-user" content="{{ auth()->id() }}">
    <meta name="api-auth-expires" content="{{ $apiAuthExpires }}">
    <meta name="api-auth-signature" content="{{ $apiAuthSignature }}">
    @endauth

    <title>{{ config('app.name', 'Laravel') }}</title>

    @auth
    <script>
        window.__authUserId = {{ auth()->id() ?? 'null' }};
    </script>
    <script>
        window.__notificationAssetSettings = @json(\App\Models\NotificationEventSetting::getCachedMap());
        window.__notificationMuteAll = @json(\App\Models\UserNotificationSetting::getBool(auth()->id(), \App\Models\UserNotificationSetting::MUTE_ALL_KEY, false));
        window.__notificationDefaultSound = @json(asset('sounds/notification.mp3'));
    </script>
    @endauth

    <!-- Fonts -->
    @if(!config('app.assets_emergency'))
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @endif

    <!-- Bootstrap (RTL) -->
    @if(!config('app.assets_emergency'))
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @endif

    <!-- FontAwesome -->
    @if(!config('app.assets_emergency'))
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
    @endif

    <!-- Persian Datepicker (Local) -->
    <link href="{{ asset('vendor/persian-datepicker/css/persian-datepicker.min.css') }}" rel="stylesheet">

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

    @if(!config('app.assets_emergency'))
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endif

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
                url('{{ asset('fonts/iransans/IRANSans.woff') }}') format('woff'),
                url('{{ asset('fonts/iransans.woff') }}') format('woff');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        </style>

</head>

<body x-data="{ mainMenuOpen: false, subMenuOpen: false, activeMenu: null, openSubMenu(name) { this.subMenuOpen = true; this.activeMenu = name; } }"
      class="font-sans antialiased overflow-x-hidden">
<div class="min-h-screen bg-gray-100">
    <x-header :breadcrumb="$breadcrumb ?? []" />
    <x-sidebar />

    <div class="mt-[60px]">
    @yield('header')
</div>

    
<main class="mt-[30px] transition-all duration-300">
    @yield('content')
    </main>
</div>

<!-- Global Notification Toast Container -->
<div id="notification-toast-container"
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 items-end pointer-events-none">
</div>

<!-- Notification Permission Banner Container -->
<div id="notification-permission-banner"
     class="fixed top-4 right-4 z-50 max-w-md w-[92vw] sm:w-[420px] pointer-events-none">
</div>

<!-- Notification Permission Help Modal -->
<div id="notification-permission-help"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-lg border border-gray-200 p-4">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-900">فعال‌سازی اعلان‌ها در Chrome</h3>
            <button type="button" data-notif-help-close
                    class="text-gray-400 hover:text-gray-600 text-lg leading-none">
                &times;
            </button>
        </div>
        <ol class="mt-3 text-sm text-gray-700 space-y-1">
            <li>روی آیکن قفل کنار آدرس کلیک کنید.</li>
            <li>گزینه <span class="font-semibold">Site settings</span> را باز کنید.</li>
            <li>در بخش <span class="font-semibold">Notifications</span> گزینه <span class="font-semibold">Allow</span> را انتخاب کنید.</li>
            <li>صفحه را Refresh کنید.</li>
        </ol>
        <div class="mt-4 flex justify-end">
            <button type="button" data-notif-help-close
                    class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                متوجه شدم
            </button>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notification-audio"
       src="{{ asset('sounds/notification.mp3') }}"
       preload="auto"></audio>

<!-- Persian Datepicker Dependencies (Local) -->
<script src="{{ asset('vendor/persian-datepicker/js/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-date.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-datepicker.min.js') }}"></script>

<!-- Bootstrap Bundle -->
@if(!config('app.assets_emergency'))
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endif

<script src="{{ asset('js/emergency-modals.js') }}" defer></script>

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
      const skipAutofill = $input.attr('data-skip-autofill') === '1';
      const hadValue = !!$input.val();
      const hadAlt = altSel ? !!$(altSel).val() : false;
      const allowInitial = hadValue || hadAlt;
      let userSelected = false;

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
        initialValue: !skipAutofill && !!$input.val(),
        autoClose: true,
        observer: !skipAutofill,
        calendar: {
          persian:   { locale: 'fa', leapYearMode: 'astronomical' },
          gregorian: { locale: 'en' }
        },
        altField: altSel || undefined,
        altFormat: 'YYYY-MM-DD',
        formatter(unix){
          if (skipAutofill && !allowInitial && !userSelected) return '';
          const self = this;
          const pdate = new persianDate(unix);
          pdate.formatPersian = self.persianDigit;
          return pdate.format(self.format);
        },
        onSelect(unix){
          userSelected = true;
          // Ensure the visible input is populated on user selection (especially when skipAutofill is enabled)
          try {
            const p = new persianDate(unix);
            p.formatPersian = true;
            $input.val(p.format('YYYY/MM/DD'));
          } catch (e) {}
          if (altSel) {
            const g = new persianDate(unix)
              .toCalendar('gregorian')
              .toLocale('en')
              .format('YYYY-MM-DD');
            $(altSel).val(g);
          }
          $input.trigger('change');
        }
      });

      if (skipAutofill && !hadValue && !hadAlt) {
        $input.val('');
        $input.attr('value', '');
        if (altSel) $(altSel).val('');
        setTimeout(() => {
          if (skipAutofill && !allowInitial && !userSelected) {
            $input.val('');
            $input.attr('value', '');
          }
        }, 0);
      }

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
      if ($i.attr('data-timepicker') === '1') return;
      // Read raw attribute to avoid jQuery camelCase data() mismatch
      const altId = $i.attr('data-alt-field') || $i.attr('data-target');
      if (altId) initPicker($i, '#' + altId); else initPicker($i, null);
    });
  });
</script>

@if(!config('app.assets_emergency'))
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endif




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
    } else {
      window.alert(@json(session('success')));
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
    } else {
      window.alert(@json(session('error')));
    }
  });
</script>
@endif


@if (session('contact_created'))
    @php($contactCreated = session('contact_created'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Swal) { return; }
            const data = @json($contactCreated);
            const convertUrl = @json(route('sales.contacts.convert_to_lead', ['contact' => $contactCreated['contact_id']]));
            const listUrl = @json(route('sales.contacts.index'));
            const csrf = @json(csrf_token());

            Swal.fire({
                icon: 'success',
                title: 'مخاطب ذخیره شد',
                text: data.contact_name
                    ? `مخاطب ${data.contact_name} ذخیره شد. آیا آن را به سرنخ فروش تبدیل کنیم؟`
                    : 'مخاطب جدید با موفقیت ذخیره شد. آیا مایل به تبدیل آن به سرنخ فروش هستید؟',
                showCancelButton: true,
                confirmButtonText: 'تبدیل به سرنخ فروش',
                cancelButtonText: 'بازگشت به لیست مخاطبین',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = convertUrl;
                    form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '">';
                    document.body.appendChild(form);
                    form.submit();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = listUrl;
                }
            });
        });
    </script>
@endif


@stack('scripts')
</body>
</html>
