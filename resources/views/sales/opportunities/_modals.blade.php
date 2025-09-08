{{-- مودال انتخاب مخاطب --}}
<div id="contactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
            <button onclick="closeContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        <div class="mb-3">
            <input id="contactSearchInput" type="text" placeholder="جستجوی نام یا موبایل…"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   autocomplete="off">
            <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
        </div>

        <div class="border border-gray-200 rounded overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700 sticky top-0">
                <tr>
                    <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
                    <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
                </tr>
                </thead>
                <tbody id="contactTableBody">
                @foreach($contacts as $c)
                    <tr class="cursor-pointer hover:bg-gray-50"
                        data-name="{{ $c->full_name }}"
                        data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}"
                        onclick="selectContact({{ $c->id }}, @js($c->full_name))">
                        <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div id="contactNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
        </div>
    </div>
</div>

{{-- مودال انتخاب سازمان --}}
<div id="organizationModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب سازمان</h3>
            <button onclick="closeOrganizationModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        <div class="mb-3">
            <input id="organizationSearchInput" type="text" placeholder="جستجوی نام سازمان یا شماره تماس…"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   autocomplete="off">
            <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
        </div>

        <div class="border border-gray-200 rounded overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700 sticky top-0">
                <tr>
                    <th class="px-4 py-2 border-b border-gray-300">نام سازمان</th>
                    <th class="px-4 py-2 border-b border-gray-300">شماره تماس</th>
                </tr>
                </thead>
                <tbody id="organizationTableBody">
                @foreach($organizations as $org)
                    <tr class="cursor-pointer hover:bg-gray-50"
                        data-name="{{ $org->name }}"
                        data-phone="{{ preg_replace('/\D+/', '', (string)($org->phone ?? '')) }}"
                        onclick="selectOrganization({{ $org->id }}, @js($org->name))">
                        <td class="px-4 py-2 border-b border-gray-200">{{ $org->name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $org->phone ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div id="organizationNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
        </div>
    </div>
</div>
