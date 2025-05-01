@props(['show' => false])

<div x-data="{ isOpen: {{ $show ? 'true' : 'false' }} }" class="fixed inset-0 z-50" x-show="isOpen">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" 
         x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false">
    </div>

    <!-- Sidebar -->
    <div class="fixed right-0 top-0 h-full w-full md:w-1/4 bg-white shadow-xl transform transition-transform duration-300 ease-in-out"
         x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
        
        <!-- Menu Header -->
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-xl font-semibold text-gray-800">منو</h2>
            <button @click="isOpen = false" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Menu Items -->
        <div class="p-4 space-y-2">
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                داشبورد
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                بازاریابی
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                فروش
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                موجودی
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                پشتیبانی
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                پروژه‌ها
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                ابزارها
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                اداری
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                اسناد
            </a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors duration-200">
                تنظیمات
            </a>
        </div>
    </div>
</div> 