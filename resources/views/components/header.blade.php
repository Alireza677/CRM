<header 
    x-data="{
        mainMenuOpen: false,
        subMenuOpen: false,
        activeMenu: null,
        openSubMenu(menuName) {
            if (!this.subMenuOpen) {
                this.activeMenu = menuName;
                this.subMenuOpen = true;
            } else if (this.activeMenu !== menuName) {
                this.activeMenu = menuName;
            } else {
                this.subMenuOpen = false;
                this.activeMenu = null;
            }
        }
    }"
    x-init="$store.menu = $data"
    class="fixed top-0 left-0 right-0 h-16 bg-white shadow-md z-50 flex items-center justify-between px-4"
>
    <!-- دکمه باز کردن منو -->
    <button 
        @click="$store.menu.mainMenuOpen = true"
        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md"
    >
        <svg x-show="!$store.menu.mainMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg x-show="$store.menu.mainMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

   <!-- لوگو در وسط -->
        <a href="{{ route('dashboard') }}" 
        class="relative flex items-center justify-center overflow-hidden group">
            <img src="{{ asset('images/admin-ajax.png') }}" 
                alt="Logo" 
                class="h-12 w-auto transition-transform duration-500 group-hover:scale-105">
        </a>

    <!-- تاریخ و ساعت شمسی یک‌خطی -->
    <div id="datetime-display" class="text-gray-700 text-sm font-medium leading-tight text-center">
        <div>{{ \Morilog\Jalali\Jalalian::now()->format('l j F Y') }}</div>
        <div id="time-now" class="font-bold text-base"></div>
    </div>

    <div class="flex items-center gap-6">
        <!-- جستجوی سراسری -->
        <form method="GET" action="{{ route('global.search') }}" class="relative hidden md:block">
            <input 
                type="text" 
                name="q" 
                placeholder="جستجو..." 
                class="pl-10 pr-3 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring focus:border-blue-400"
            >
            <button type="submit" class="absolute left-0 top-1 text-gray-500 hover:text-blue-600 px-2">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <!-- آیکن‌های میانبر: تقویم و وظیفه‌ها -->
            <div class="hidden md:flex items-center gap-2">
                <!-- تقویم -->
                {{-- تقویم --}}
            <a href="{{ route('calendar.index') }}"
               class="flex flex-col items-center justify-center w-12 h-12 hover:bg-gray-50 rounded-lg transition"
               title="تقویم">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <rect x="7" y="14" width="4" height="4" rx="1"></rect>
                </svg>
                <span class="text-[13px] leading-none text-gray-600 mt-0.5">تقویم</span>
            </a>

            {{-- وظیفه --}}
            <a href="{{ route('activities.index') }}"
               class="flex flex-col items-center justify-center w-12 h-12 hover:bg-gray-50 rounded-lg transition"
               title="وظیفه">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 10v10a2 2 0 0 1-2 2H7l-4-4V6a2 2 0 0 1 2-2h4"></path>
                    <path d="M8 6h.01"></path>
                </svg>
                <span class="text-[13px] leading-none text-gray-600 mt-0.5">وظیفه</span>
            </a>
            </div>

        {{-- اعلان‌ها و منوی کاربر فقط برای کاربران لاگین --}}
            @auth
            
                <!-- اعلان‌ها -->
                <div class="relative" x-data="{ showNotifications: false }">

                    @php
                        $unreadCount = auth()->check()
                            ? auth()->user()->unreadNotifications()->count()   // شمارش امن با کوئری
                            : 0;
                    @endphp

                    <button @click="showNotifications = !showNotifications" class="relative text-gray-600 hover:text-gray-800 focus:outline-none">
                        <!-- آیکن زنگ -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>

                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ $unreadCount }}
                            </span>
                        @endif
                    </button>
                       

                    <!-- لیست اعلان‌ها -->
                    <div x-show="showNotifications"
                        x-cloak
                        @click.away="showNotifications = false"
                        class="absolute left-0 mt-2 w-80 bg-white shadow-lg rounded-lg border border-gray-200 z-50"
                        style="max-height: 400px; overflow-y: auto;">
                        <div class="flex justify-between items-center p-4 border-b font-semibold text-gray-700">
                            <span>اعلان‌های اخیر</span>
                            <button onclick="clearNotificationList()" class="text-xs text-red-500 hover:text-red-700 hover:underline">
                                پاک کردن لیست
                            </button>
                        </div>

                        <div id="notification-list">
                            @php
                                $latestUnread = auth()->user()->unreadNotifications()->latest()->take(10)->get();
                            @endphp

                            @forelse($latestUnread as $notification)
                                <div class="px-4 py-2 text-sm text-gray-800 hover:bg-gray-50 border-b">
                                    <a href="{{ route('notifications.index') }}">
                                        {{ $notification->data['message'] ?? 'اعلان جدیدی دارید' }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-sm text-gray-500">اعلان خوانده‌نشده‌ای وجود ندارد.</div>
                            @endforelse
                        </div>

                        <div class="text-center border-t p-2">
                            <a href="{{ route('notifications.index') }}" class="text-blue-600 hover:underline text-sm">
                                مشاهده همه اعلان‌ها
                            </a>
                        </div>
                    </div>
                </div>

                <!-- نام کاربر و منوی کشویی -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="text-gray-600 hover:text-gray-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                    </button>

                    <div x-show="open" x-cloak @click.away="open = false"
                        class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">پروفایل</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">خروج</button>
                        </form>
                    </div>
                </div>
            @endauth
    </div>
</header>

<!-- Spacer -->
<div class="h-16"></div>



<style>
/* تعریف رنگ و افکت درخشش */
@keyframes sheen {
  0% {
    transform: rotate(25deg) translate(-200%, -50%);
    opacity: 0;
  }
  50% {
    opacity: 0.6;
  }
  100% {
    transform: rotate(25deg) translate(200%, -50%);
    opacity: 0;
  }
}

/* افکت نور روی لوگو */
a.relative::after {
  content: "";
  position: absolute;
  top: 0;
  left: -75%;
  width: 50%;
  height: 100%;
  background: linear-gradient(
    120deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.5) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  transform: skewX(-25deg);
}

/* اجرای انیمیشن هنگام hover */
a.relative:hover::after {
  animation: sheen 1s forwards;
}
</style>

<script>
    function clearNotificationList() {
        const container = document.getElementById('notification-list');
        if (container) {
            container.innerHTML = `
                <div class="p-4 text-sm text-gray-400 text-center">
                    هیچ اعلانی برای نمایش وجود ندارد.
                </div>
            `;
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('time-now').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateTime();
        setInterval(updateTime, 1000);
    });
</script>