<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">

<div class="bg-white rounded-lg shadow-sm border p-4 mb-6 w-full max-w-md">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">فیلدهای کلیدی</h2>

    <div class="space-y-3">
        <!-- نام فرصت فروش -->
        <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
            <span class="text-gray-600">نام فرصت فروش :</span>
            <span class="text-gray-900">{{ $opportunity->name ?? '-' }}</span>
        </div>

        <!-- ارجاع به -->
        <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
            <span class="text-gray-600">ارجاع به :</span>
            <span class="text-gray-900">{{ $opportunity->assignedTo->name ?? '-' }}</span>
        </div>

        <!-- مرحله فروش -->
        <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
            <span class="text-gray-600">مرحله فروش :</span>
            <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                {{ $opportunity->stage ?? '-' }}
            </span>
        </div>
    </div>
</div>


    {{-- یادداشت‌ها --}}
    <div class="bg-blue-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-blue-800">یادداشت‌ها</h3>
            <p class="text-sm text-blue-600 mt-1">تعداد یادداشت‌ها: {{ $opportunity->notes->count() ?? 0 }}</p>
        </div>
        <i class="fas fa-sticky-note text-3xl text-blue-400"></i>
    </div>

    {{-- فعالیت‌ها --}}
    <div class="bg-green-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-green-800">فعالیت‌ها</h3>
            <p class="text-sm text-green-600 mt-1">در حال انجام: 0</p>
        </div>
        <i class="fas fa-tasks text-3xl text-green-400"></i>
    </div>

    {{-- تماس‌ها --}}
    <div class="bg-purple-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-purple-800">تماس‌ها</h3>
            <p class="text-sm text-purple-600 mt-1">هنوز تماسی ثبت نشده</p>
        </div>
        <i class="fas fa-phone-alt text-3xl text-purple-400"></i>
    </div>

    {{-- مخاطبین --}}
    <div class="bg-yellow-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-yellow-800">مخاطبین</h3>
            <p class="text-sm text-yellow-600 mt-1">{{ optional($opportunity->contact)->name ?? 'بدون مخاطب' }}</p>
        </div>
        <i class="fas fa-user-friends text-3xl text-yellow-400"></i>
    </div>

    {{-- پیش‌فاکتورها --}}
    <div class="bg-pink-50 rounded-lg p-4 shadow flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-pink-800">پیش‌فاکتورها</h3>
            <p class="text-sm text-pink-600 mt-1">تعداد: 0</p>
        </div>
        <i class="fas fa-file-invoice text-3xl text-pink-400"></i>
    </div>
</div>
