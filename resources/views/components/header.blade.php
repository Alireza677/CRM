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
    <a href="{{ route('dashboard') }}" class="flex items-center justify-center">
        <img src="{{ asset('images/admin-ajax.png') }}" alt="Logo" class="h-10 w-auto">
    </a>

    <!-- آیکن زنگ و نام کاربر -->
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

        <!-- اعلان‌ها -->
<div class="relative" x-data="{ showNotifications: false }">
    <button @click="showNotifications = !showNotifications" class="relative text-gray-600 hover:text-gray-800 focus:outline-none">
        <!-- آیکن زنگ -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ auth()->user()->unreadNotifications->count() }}
            </span>
        @endif
    </button>
    
    <!-- لیست اعلان‌ها -->
<div
    x-show="showNotifications"
    x-cloak
    @click.away="showNotifications = false"
    class="absolute left-0 mt-2 w-80 bg-white shadow-lg rounded-lg border border-gray-200 z-50"
    style="max-height: 400px; overflow-y: auto;"
>
    <div class="flex justify-between items-center p-4 border-b font-semibold text-gray-700">
        <span>اعلان‌های اخیر</span>
        <button
            onclick="clearNotificationList()"
            class="text-xs text-red-500 hover:text-red-700 hover:underline"
        >
            پاک کردن لیست
        </button>
    </div>

    <div id="notification-list">
        @forelse(auth()->user()->unreadNotifications->take(10) as $notification)
            <div class="px-4 py-2 text-sm text-gray-800 hover:bg-gray-50 border-b">
                <a href="{{ route('notifications.index') }}">
                    {{ $notification->data['message'] ?? 'اعلان جدیدی دارید' }}
                </a>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $notification->created_at->diffForHumans() }}
                </div>
            </div>
        @empty
            <div class="p-4 text-sm text-gray-500">
                اعلان خوانده‌نشده‌ای وجود ندارد.
            </div>
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

            <div
                x-show="open"
                x-cloak
                @click.away="open = false"
                class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50"
            >
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    پروفایل
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        خروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Spacer -->
<div class="h-16"></div>




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
