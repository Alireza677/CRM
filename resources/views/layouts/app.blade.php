<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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
                url('{{ asset('fonts/iransans/IRANSans.woff') }}') format('woff');
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

@if(!config('app.assets_emergency'))
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endif

@auth
<script>
    (function () {
        const feedUrl = @json(route('notifications.feed.latest'));
        const toastContainer = document.getElementById('notification-toast-container');
        const audioEl = document.getElementById('notification-audio');

        if (!toastContainer || !feedUrl) {
            return;
        }

        const SEEN_KEY = 'crm_seen_notification_ids';
        let audioUnlocked = false;
        let seenIds;
        try {
            const raw = window.localStorage.getItem(SEEN_KEY);
            const arr = raw ? JSON.parse(raw) : [];
            seenIds = new Set(Array.isArray(arr) ? arr : []);
        } catch (e) {
            seenIds = new Set();
        }

        function persistSeenIds() {
            try {
                window.localStorage.setItem(SEEN_KEY, JSON.stringify(Array.from(seenIds)));
            } catch (e) {
                // ignore storage errors
            }
        }

        function unlockAudioOnce() {
            if (!audioEl || audioUnlocked) return;
            try {
                const playPromise = audioEl.play();
                if (playPromise && typeof playPromise.then === 'function') {
                    playPromise.then(function () {
                        audioEl.pause();
                        audioEl.currentTime = 0;
                        audioUnlocked = true;
                    }).catch(function () {
                        // user gesture ممکن است کافی نباشد؛ ساکت می‌مانیم
                    });
                } else {
                    audioUnlocked = true;
                }
            } catch (e) {
                // ignore
            }
        }

        document.addEventListener('click', unlockAudioOnce, { once: true });
        document.addEventListener('keydown', unlockAudioOnce, { once: true });

        function playNotificationSound() {
            if (!audioEl || !audioUnlocked) return;
            try {
                audioEl.currentTime = 0;
                const playPromise = audioEl.play();
                if (playPromise && typeof playPromise.then === 'function') {
                    playPromise.catch(function () {
                        // autoplay may be blocked; ignore
                    });
                }
            } catch (e) {
                // ignore audio errors
            }
        }

        function showToast(notification) {
    if (!notification || !notification.id || !toastContainer) return;

    const wrapper = document.createElement('div');
    wrapper.className =
        'pointer-events-auto max-w-sm w-80 bg-white border border-blue-200 shadow-lg ' +
        'rounded-xl p-3 flex gap-3 items-start animate-fade-in-down';

    // آیکن
    const iconWrapper = document.createElement('div');
    iconWrapper.innerHTML = `
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-100">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2z" />
            </svg>
        </span>
    `;

    // محتوای اعلان
    const content = document.createElement('div');
    content.className = 'flex-1';

    const headerRow = document.createElement('div');
    headerRow.className = 'flex items-start justify-between gap-2';

    const titleEl = document.createElement('div');
    titleEl.className = 'text-sm font-semibold text-gray-900 leading-snug';

    const messageText = notification.message || 'اعلان جدیدی دارید';

    if (notification.url) {
        const link = document.createElement('a');
        link.href = notification.url;
        link.textContent = messageText;
        link.className = 'hover:text-blue-600 hover:underline';
        titleEl.appendChild(link);
    } else {
        titleEl.textContent = messageText;
    }

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'text-gray-400 hover:text-gray-600 text-lg leading-none flex-shrink-0';
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', function () {
        if (wrapper.parentNode) {
            wrapper.parentNode.removeChild(wrapper);
        }
    });

    headerRow.appendChild(titleEl);
    headerRow.appendChild(closeBtn);

    const metaRow = document.createElement('div');
    metaRow.className = 'mt-1 flex items-center justify-between text-[11px] text-gray-400';

    const timeEl = document.createElement('span');
    if (notification.created_at) {
        try {
            const d = new Date(notification.created_at);
            if (!isNaN(d.getTime())) {
                timeEl.textContent = d.toLocaleString('fa-IR');
            }
        } catch (e) {
            // ignore parse errors
        }
    }

    metaRow.appendChild(timeEl);

    if (notification.url) {
        const openBtn = document.createElement('button');
        openBtn.type = 'button';
        openBtn.textContent = 'باز کردن';
        openBtn.className = 'text-blue-600 hover:text-blue-700 font-semibold';
        openBtn.addEventListener('click', function () {
            window.location.href = notification.url;
        });
        metaRow.appendChild(openBtn);
    }

    content.appendChild(headerRow);
    if (timeEl.textContent) {
        content.appendChild(metaRow);
    }

    wrapper.appendChild(iconWrapper);
    wrapper.appendChild(content);

    toastContainer.appendChild(wrapper);

    setTimeout(function () {
        if (wrapper.parentNode) {
            wrapper.parentNode.removeChild(wrapper);
        }
    }, 7000);
}


        function handleNotifications(list) {
            if (!Array.isArray(list) || !list.length) {
                return;
            }

            // نمایش قدیمی‌ترها اول
            const ordered = list.slice().reverse();

            let hasNew = false;

            ordered.forEach(function (item) {
                if (!item || !item.id) return;
                if (seenIds.has(item.id)) {
                    return;
                }
                seenIds.add(item.id);
                hasNew = true;
                showToast(item);
            });

            if (hasNew) {
                persistSeenIds();
                playNotificationSound();
            }
        }

        function fetchLatest() {
            fetch(feedUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed: ' + response.status);
                    }
                    const contentType = response.headers.get('Content-Type') || '';
                    if (contentType.indexOf('application/json') === -1) {
                        throw new Error('Unexpected content type');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || typeof payload !== 'object') return;
                    handleNotifications(payload.data || []);
                })
                .catch(function () {
                    // در صورت خطا سکوت می‌کنیم تا UI مختل نشود
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // بار اول بعد از لود صفحه
            fetchLatest();
            // هر ۱۵ ثانیه یک بار
            setInterval(fetchLatest, 15000);
        });
    })();
</script>
@endauth

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
