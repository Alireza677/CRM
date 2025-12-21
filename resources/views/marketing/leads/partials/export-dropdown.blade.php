<details class="relative">
    <summary class="inline-flex cursor-pointer list-none items-center px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-md shadow hover:bg-emerald-700">
        <i class="fas fa-file-export ml-1 text-sm"></i>
        خروجی
        <i class="fas fa-chevron-down mr-2 text-xs"></i>
    </summary>
    <div class="absolute left-0 mt-2 w-36 rounded-md border border-gray-200 bg-white shadow-lg z-10">
        <a href="{{ route('sales.contacts.export.format', ['format' => 'csv']) }}"
           class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            CSV
        </a>
        <a href="{{ route('sales.contacts.export.format', ['format' => 'xlsx']) }}"
           class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            Excel
        </a>
    </div>
</details>
