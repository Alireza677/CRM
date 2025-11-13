@props(['active' => false])

<!-- Sidebar Wrapper -->
<div class="relative z-50">
    <!-- Sidebar Overlay -->
    <div 
        x-show="$store.menu.mainMenuOpen"
        x-cloak
        @click="$store.menu.mainMenuOpen = false; $store.menu.subMenuOpen = false; $store.menu.activeMenu = null;"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <aside 
        x-show="$store.menu.mainMenuOpen"
        x-cloak
        class="fixed top-0 right-0 h-full bg-white shadow-lg z-50 w-[50vw] md:w-[250px] transform transition-transform duration-300 ease-in-out"
        :class="{ 'translate-x-0': $store.menu.mainMenuOpen, 'translate-x-full': !$store.menu.mainMenuOpen }"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        dir="rtl"
        @click.away="$store.menu.mainMenuOpen = false; $store.menu.subMenuOpen = false; $store.menu.activeMenu = null;"
    >

        <!-- دکمه داشبورد -->
        <div class="p-4 border-b border-gray-200">
            <a href="{{ route('dashboard') }}"
                class="w-full block text-right px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded transition duration-200 text-sm font-medium shadow">
                 داشبورد
            </a>
        </div>

        <!-- Main Menu Items -->
        <nav class="p-4 space-y-2">
            
            <button @click="$store.menu.openSubMenu('marketing')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='marketing' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                بازاریابی
            </button>

            <button @click="$store.menu.openSubMenu('sales')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='sales' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                فروش
            </button>

            <button @click="$store.menu.openSubMenu('projects')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='projects' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                پروژه‌ها
            </button>

            <button @click="$store.menu.openSubMenu('inventory')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='inventory' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                تأمین و خرید
            </button>

            {{--<button @click="$store.menu.openSubMenu('print-templates')"
                     class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                     :class="$store.menu.activeMenu==='print-templates' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                قالب‌های پرینت
            </button>--}}

            {{--<button @click="$store.menu.openSubMenu('forms')"
                     class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                     :class="$store.menu.activeMenu==='forms' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                فرم‌ها
            </button>--}}

            <button @click="$store.menu.openSubMenu('documents')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='documents' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                اسناد
            </button>

            <button @click="$store.menu.openSubMenu('calendar')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='calendar' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                تقویم
            </button>        

            <button @click="$store.menu.openSubMenu('settings')"
                    class="w-full text-right px-4 py-2 text-gray-700 rounded transition duration-200"
                    :class="$store.menu.activeMenu==='settings' ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-50'">
                تنظیمات
            </button>

            <a href="{{ route('employee.portal.index') }}" class="w-full block text-right px-4 py-2 text-gray-700 rounded transition duration-200 hover:bg-gray-50">
                پرتال کارمند
            </a>
        </nav>
    </aside>

    <!-- Submenu (Left of Main Menu) -->
    <aside 
        x-show="$store.menu.subMenuOpen"
        x-cloak
        class="fixed top-0 right-[50vw] h-full bg-white shadow-lg z-40 w-[50vw] md:right-[250px] md:w-[250px] transform transition-transform duration-300 ease-in-out"
        :class="{ 'translate-x-0': $store.menu.subMenuOpen, 'translate-x-full': !$store.menu.subMenuOpen }"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        dir="rtl"
        @mousedown.away="$store.menu.subMenuOpen = false"
    >

        <div class="flex flex-col h-full">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">
                    <template x-if="$store.menu.activeMenu === 'calendar'">تقویم</template>
                    <template x-if="$store.menu.activeMenu === 'marketing'">بازاریابی</template>
                    <template x-if="$store.menu.activeMenu === 'sales'">فروش</template>
                    <template x-if="$store.menu.activeMenu === 'projects'">پروژه‌ها</template>
                    <template x-if="$store.menu.activeMenu === 'inventory'">موجودی</template>
                    <template x-if="$store.menu.activeMenu === 'print-templates'">قالب‌های پرینت</template>
                    <template x-if="$store.menu.activeMenu === 'forms'">فرم‌ها</template>
                    <template x-if="$store.menu.activeMenu === 'settings'">تنظیمات</template>
                </h2>
                <button @click="$store.menu.subMenuOpen = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
                <template x-if="$store.menu.activeMenu === 'calendar'">
                    <div class="space-y-2">
                        <a href="{{ route('calendar.index') }}" class="menu-item">مشاهده تقویم</a>
                        @role('admin')
                            <a href="{{ route('holidays.index') }}" class="menu-item">مدیریت تعطیلات</a>
                        @endrole
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'sales'">
                    <div class="space-y-2">
                        <a href="{{ route('sales.opportunities.index') }}" class="menu-item">فرصت‌های فروش</a>
                        <a href="{{ route('sales.contacts.index') }}" class="menu-item">مخاطبین</a>
                        <a href="{{ route('sales.organizations.index') }}" class="menu-item">سازمان‌ها</a>
                        <a href="{{ route('sales.proformas.index') }}" class="menu-item">پیش‌فاکتور</a>
                    </div>
                </template>

                <!-- Admin-only: Roles Permission Matrix link moved to settings -->

                <template x-if="$store.menu.activeMenu === 'marketing'">
                    <div class="space-y-2">
                        <a href="{{ route('marketing.leads.index') }}" class="menu-item">سرنخ‌های فروش</a>
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'projects'">
                    <div class="space-y-2">
                        <a href="/projects" class="menu-item">لیست پروژه‌ها</a>
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'inventory'">
                    <div class="space-y-2">
                        <a href="{{ route('inventory.products.index') }}" class="menu-item">محصولات</a>
                        <a href="{{ route('inventory.suppliers.index') }}" class="menu-item">تأمین‌کنندگان</a>
                        <a href="{{ route('inventory.purchase-orders.index') }}" class="menu-item">سفارش‌های خرید</a>
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'print-templates'">
                    <div class="space-y-2">
                        <a href="{{ route('print-templates.index') }}" class="menu-item">لیست قالب‌ها</a>
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'forms'">
                    <div class="space-y-2">
                        <a href="{{ route('forms.index') }}" class="menu-item">لیست فرم‌ها</a>
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'settings'">
                    <div class="space-y-2">
                    @role('admin')
                        <a href="{{ route('settings.general') }}" class="menu-item">تنظیمات عمومی</a>
                        <a href="{{ route('settings.users.index') }}" class="menu-item">مدیریت کاربران</a>
                        <a href="{{ route('settings.workflows.index') }}" class="menu-item">گردش کارها</a>
                        <a href="{{ route('settings.automation.edit') }}" class="menu-item">اتوماسیون</a>
                        <a href="{{ route('settings.notifications.index') }}" class="menu-item">تنظیمات اعلان‌ها</a>
                        <a href="{{ route('roles.matrix') }}" class="menu-item">ماتریس دسترسی نقش‌ها</a>
                    @endrole
                    @can('reports.view')
                        <a href="{{ route('admin.role-permissions') }}" class="menu-item">گزارش نقش‌ها و دسترسی‌ها</a>
                    @endcan
                    </div>
                </template>

                <template x-if="$store.menu.activeMenu === 'documents'">
                    <div class="space-y-2">
                        @can('viewAny', \App\Models\Document::class)
                            <a href="{{ route('sales.documents.index') }}" class="menu-item">همه اسناد</a>
                        @endcan
                        <a href="{{ route('tools.sms.create') }}" class="menu-item">ارسال پیامک</a>
                    </div>
                </template>

                <!-- Reports quick links under documents menu -->
                <template x-if="$store.menu.activeMenu === 'documents'">
                    <div class="space-y-2">
                        <a href="{{ route('reports.dashboard') }}" class="menu-item">داشبورد گزارش‌ها</a>
                        <a href="{{ route('reports.index') }}" class="menu-item">همه گزارش‌ها</a>
                    </div>
                </template>

                <!-- Roles & permissions report moved to settings -->

            </nav>
        </div>
    </aside>
</div>

<!-- استایل کمکی -->
<style>
   /* موبایل: منوی اصلی 50% صفحه، زیرمنو 50% باقی‌مانده */
@media (max-width: 767.98px) {
  /* لایه اول (منوی اصلی) */
  .relative.z-50 > aside:nth-of-type(1) {
    width: 50vw !important;
    right: 0 !important;
  }

  /* لایه دوم (زیرمنو) */
  .relative.z-50 > aside:nth-of-type(2) {
    width: 50vw !important;
    right: 50vw !important; /* دقیقاً کنار منوی اصلی باز می‌شود */
  }
}

/* دسکتاپ: هر کدام 250px مثل قبل */
@media (min-width: 768px) {
  /* لایه اول (منوی اصلی) */
  .relative.z-50 > aside:nth-of-type(1) {
    width: 250px !important;
    right: 0 !important;
  }

  /* لایه دوم (زیرمنو) */
  .relative.z-50 > aside:nth-of-type(2) {
    width: 250px !important;
    right: 250px !important; /* کنار لایه اول */
  }
}

/* زیرمنو: آیتم‌ها عمودی زیر هم */
@media (max-width: 767.98px), (min-width: 768px) {
  /* ظرف لیست زیرمنو را ستونی کن */
  .relative.z-50 > aside:nth-of-type(2) nav > div {
    display: flex !important;
    flex-direction: column !important;
    gap: 0.5rem !important; /* معادل space-y-2 */
  }

  /* هر لینک یک ردیف کامل بگیرد */
  .relative.z-50 > aside:nth-of-type(2) nav .menu-item {
    display: block !important;
    width: 100% !important;
  }
}

</style>
