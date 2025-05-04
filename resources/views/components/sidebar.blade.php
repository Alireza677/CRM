@props(['active' => false])

<!-- Sidebar Wrapper -->
<div x-data="{ isOpen: false, activeMenu: null }" class="relative z-50">
    <!-- Sidebar Toggle Button -->
    <button 
        @click="isOpen = !isOpen"
        class="fixed top-4 right-4 z-50 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md"
    >
        <svg x-show="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg x-show="isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Sidebar Overlay -->
    <div 
        x-show="isOpen"
        @click="isOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Sidebar Content -->
    <aside 
        x-show="isOpen"
        class="fixed top-0 right-0 h-full bg-white shadow-lg z-50 w-full sm:w-full md:w-1/2 transform transition-transform duration-300 ease-in-out"
        :class="{ 'translate-x-0': isOpen, 'translate-x-full': !isOpen }"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        dir="rtl"
        @click.away="isOpen = false"
    >
        <div class="flex h-full">
            <!-- Main Menu Panel -->
            <div class="w-1/3 border-l border-gray-200 bg-gray-50">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">منو اصلی</h2>
                </div>
                <nav class="p-2 space-y-1">
                    <button 
                        @click="activeMenu = 'dashboard'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'dashboard' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>داشبورد</span>
                    </button>

                    <button 
                        @click="activeMenu = 'sales'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'sales' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>فروش</span>
                    </button>

                    <button 
                        @click="activeMenu = 'marketing'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'marketing' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                        <span>بازاریابی</span>
                    </button>

                    <button 
                        @click="activeMenu = 'projects'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'projects' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>پروژه‌ها</span>
                    </button>

                    <button 
                        @click="activeMenu = 'inventory'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'inventory' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>موجودی</span>
                    </button>

                    <button 
                        @click="activeMenu = 'print-templates'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'print-templates' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>قالب‌های پرینت</span>
                    </button>

                    <button 
                        @click="activeMenu = 'forms'"
                        class="w-full flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                        :class="{ 'bg-blue-50 text-blue-600': activeMenu === 'forms' }"
                    >
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>فرم‌ها</span>
                    </button>
                </nav>
            </div>

            <!-- Sub Menu Panel -->
            <div class="w-2/3">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <template x-if="activeMenu === 'dashboard'">داشبورد</template>
                        <template x-if="activeMenu === 'sales'">فروش</template>
                        <template x-if="activeMenu === 'marketing'">بازاریابی</template>
                        <template x-if="activeMenu === 'projects'">پروژه‌ها</template>
                        <template x-if="activeMenu === 'inventory'">موجودی</template>
                        <template x-if="activeMenu === 'print-templates'">قالب‌های پرینت</template>
                        <template x-if="!activeMenu">
                            <div class="flex items-center justify-center h-32">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </template>
                    </h2>
                </div>
                <nav class="p-4 space-y-2">
                    <!-- Sales Submenu -->
                    <template x-if="activeMenu === 'sales'">
                        <div class="space-y-2">
                            <a href="{{ route('sales.opportunities.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/sales/opportunities') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>فرصت‌های فروش</span>
                            </a>
                            <a href="{{ route('sales.contacts.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/sales/contacts') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>مخاطبین</span>
                            </a>
                            <a href="{{ route('sales.organizations.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/sales/organizations') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>سازمان‌ها</span>
                            </a>
                            <a href="{{ route('sales.proformas.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/sales/proformas') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>پیش‌فاکتور</span>
                            </a>
                        </div>
                    </template>

                    <!-- Marketing Submenu -->
                    <template x-if="activeMenu === 'marketing'">
                        <div class="space-y-2">
                            <a href="{{ route('marketing.leads.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/marketing/leads') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>سرنخ‌های فروش</span>
                            </a>
                        </div>
                    </template>

                    <!-- Projects Submenu -->
                    <template x-if="activeMenu === 'projects'">
                        <div class="space-y-2">
                            <a href="/projects" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname === '/projects' }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>لیست پروژه‌ها</span>
                            </a>
                        </div>
                    </template>

                    <!-- Inventory Submenu -->
                    <template x-if="activeMenu === 'inventory'">
                        <div class="space-y-2">
                            <a href="{{ route('inventory.products.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/inventory/products') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>محصولات</span>
                            </a>
                            <a href="{{ route('inventory.suppliers.index') }}"
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/inventory/suppliers') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>تأمین‌کنندگان</span>
                            </a>
                            <a href="{{ route('inventory.purchase-orders.index') }}"
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/inventory/purchase-orders') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>سفارش‌های خرید</span>
                            </a>
                        </div>
                    </template>

                    <!-- Print Templates Submenu -->
                    <template x-if="activeMenu === 'print-templates'">
                        <div class="space-y-2">
                            <a href="{{ route('print-templates.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/print-templates') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>لیست قالب‌ها</span>
                            </a>
                        </div>
                    </template>

                    <!-- Forms Submenu -->
                    <template x-if="activeMenu === 'forms'">
                        <div class="space-y-2">
                            <a href="{{ route('forms.index') }}" 
                               class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                               :class="{ 'bg-blue-50 text-blue-600': window.location.pathname.includes('/forms') }"
                            >
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>لیست فرم‌ها</span>
                            </a>
                        </div>
                    </template>
                </nav>
            </div>
        </div>
    </aside>
</div> 