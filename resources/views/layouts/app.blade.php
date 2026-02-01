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
        if (window.PresenceService) return;

        const heartbeatUrl = @json(route('presence.heartbeat'));
        const statusUrl = @json(route('presence.status'));
        const currentUserId = {{ auth()->id() ?? 'null' }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const apiAuth = {
            user: document.querySelector('meta[name="api-auth-user"]')?.getAttribute('content') || '',
            expires: document.querySelector('meta[name="api-auth-expires"]')?.getAttribute('content') || '',
            signature: document.querySelector('meta[name="api-auth-signature"]')?.getAttribute('content') || '',
        };
        const ONLINE_WINDOW_MS = 30000;
        const HEARTBEAT_MS = 10000;
        const STATUS_MS = 12000;
        const HEARTBEAT_TIMEOUT_MS = 5000;

        const watchers = new Set();
        const watchedIds = new Set();
        const state = new Map();
        let heartbeatTimer = null;
        let statusTimer = null;
        let started = false;
        let lastServerTimeMs = null;

        function normalizeIds(ids) {
            return Array.from(new Set(
                (ids || [])
                    .map((id) => Number(id))
                    .filter((id) => Number.isInteger(id) && id > 0)
            ));
        }

        function parseTimeMs(value) {
            if (!value) return null;
            const ms = Date.parse(value);
            return Number.isNaN(ms) ? null : ms;
        }

        function setUserState(userId, payload, serverTimeMs) {
            if (!userId || !payload) return false;
            const prev = state.get(userId);
            const lastSeen = payload.last_seen_at || null;
            const lastSeenMs = parseTimeMs(lastSeen);
            let online = typeof payload.is_online === 'boolean' ? payload.is_online : false;

            if (lastSeenMs && serverTimeMs) {
                online = serverTimeMs - lastSeenMs <= ONLINE_WINDOW_MS;
            }

            const next = { online, last_seen_at: lastSeen };
            state.set(userId, next);
            return !prev || prev.online !== next.online || prev.last_seen_at !== next.last_seen_at;
        }

        function notify(updatedIds, serverTimeMs) {
            watchers.forEach((watcher) => {
                const data = {};
                const ids = updatedIds && updatedIds.length ? updatedIds : Array.from(watcher.ids);
                ids.forEach((id) => {
                    if (watcher.ids.has(id) && state.has(id)) {
                        data[id] = state.get(id);
                    }
                });
                if (Object.keys(data).length) {
                    watcher.callback(data, { server_time_ms: serverTimeMs });
                }
            });
        }

        async function sendHeartbeat() {
            if (!heartbeatUrl || !currentUserId) return;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), HEARTBEAT_TIMEOUT_MS);
            try {
                const response = await fetch(heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        ...(apiAuth.user && apiAuth.expires && apiAuth.signature ? {
                            'X-Api-User': apiAuth.user,
                            'X-Api-Expires': apiAuth.expires,
                            'X-Api-Signature': apiAuth.signature,
                        } : {}),
                    },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (!response.ok) {
                    throw new Error('heartbeat_failed');
                }
                const payload = await response.json().catch(() => ({}));
                lastServerTimeMs = parseTimeMs(payload?.server_time) || Date.now();
                const updated = setUserState(currentUserId, { last_seen_at: payload?.server_time, is_online: true }, lastServerTimeMs);
                if (updated) {
                    notify([currentUserId], lastServerTimeMs);
                }
            } catch (error) {
                const updated = setUserState(currentUserId, { last_seen_at: null, is_online: false }, lastServerTimeMs || Date.now());
                if (updated) {
                    notify([currentUserId], lastServerTimeMs || Date.now());
                }
            } finally {
                clearTimeout(timeoutId);
            }
        }

        async function pollStatus() {
            if (!statusUrl || !watchedIds.size) return;
            const ids = Array.from(watchedIds);
            const url = new URL(statusUrl, window.location.origin);
            ids.forEach((id) => url.searchParams.append('user_ids[]', id));
            try {
                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(apiAuth.user && apiAuth.expires && apiAuth.signature ? {
                            'X-Api-User': apiAuth.user,
                            'X-Api-Expires': apiAuth.expires,
                            'X-Api-Signature': apiAuth.signature,
                        } : {}),
                    },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error('status_failed');
                }
                const payload = await response.json().catch(() => ({}));
                const serverTimeMs = parseTimeMs(payload?.server_time) || Date.now();
                lastServerTimeMs = serverTimeMs;
                const data = payload?.data || {};
                const updatedIds = [];
                Object.entries(data).forEach(([userId, info]) => {
                    const id = Number(userId);
                    if (!Number.isInteger(id)) return;
                    const changed = setUserState(id, info || {}, serverTimeMs);
                    if (changed) {
                        updatedIds.push(id);
                    }
                });
                if (updatedIds.length) {
                    notify(updatedIds, serverTimeMs);
                }
            } catch (error) {
                // keep last known states on failure
            }
        }

        function startIntervals() {
            sendHeartbeat();
            pollStatus();
            heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_MS);
            statusTimer = setInterval(pollStatus, STATUS_MS);
        }

        function stopIntervals() {
            if (heartbeatTimer) {
                clearInterval(heartbeatTimer);
                heartbeatTimer = null;
            }
            if (statusTimer) {
                clearInterval(statusTimer);
                statusTimer = null;
            }
        }

        function start() {
            if (started) return;
            started = true;
            stopIntervals();
            startIntervals();
        }

        function recomputeWatchedIds() {
            watchedIds.clear();
            watchers.forEach((watcher) => {
                watcher.ids.forEach((id) => watchedIds.add(id));
            });
        }

        function watch(userIds, callback) {
            const ids = new Set(normalizeIds(userIds));
            if (!ids.size || typeof callback !== 'function') {
                return () => {};
            }
            const watcher = { ids, callback };
            watchers.add(watcher);
            ids.forEach((id) => watchedIds.add(id));
            const initial = {};
            ids.forEach((id) => {
                if (state.has(id)) {
                    initial[id] = state.get(id);
                }
            });
            if (Object.keys(initial).length) {
                callback(initial, { server_time_ms: lastServerTimeMs });
            }

            return function unwatch() {
                watchers.delete(watcher);
                recomputeWatchedIds();
                // Keep a single poller alive per session; no per-watch teardown.
            };
        }

        window.addEventListener('offline', () => {
            if (currentUserId) {
                const updated = setUserState(currentUserId, { last_seen_at: null, is_online: false }, lastServerTimeMs || Date.now());
                if (updated) {
                    notify([currentUserId], lastServerTimeMs || Date.now());
                }
            }
            stopIntervals();
        });

        function getSnapshot() {
            const snapshot = {};
            state.forEach((value, key) => {
                snapshot[key] = value;
            });
            return snapshot;
        }

        window.addEventListener('online', () => {
            start();
        });

        window.PresenceService = { start, watch, getSnapshot };
        window.PresenceService.start();
    })();
</script>
@endauth

@auth
<script>
    (function () {
        if (!window.PresenceService) return;
        const indicators = Array.from(document.querySelectorAll('[data-presence-indicator]'));
        if (!indicators.length) return;
        const userIds = indicators
            .map((el) => Number(el.dataset.userId))
            .filter((id) => Number.isInteger(id) && id > 0);

        function updateIndicator(el, online) {
            el.classList.remove('bg-white', 'bg-green-500');
            if (online) {
                el.classList.add('bg-green-500');
                el.classList.remove('border', 'border-gray-400', 'text-gray-400');
                el.textContent = '';
            } else {
                el.classList.add('bg-white');
                el.classList.remove('bg-green-500');
                el.classList.add('border', 'border-gray-400', 'text-gray-400');
                el.textContent = '×';
            }
        }

        window.PresenceService.watch(userIds, (data) => {
            Object.entries(data).forEach(([userId, info]) => {
                indicators
                    .filter((el) => Number(el.dataset.userId) === Number(userId))
                    .forEach((el) => updateIndicator(el, !!info?.online));
            });
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
