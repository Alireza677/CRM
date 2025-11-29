{{-- مودال انتخاب مخاطب --}}
@php $contacts = $contacts ?? collect(); @endphp
<div id="leadContactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
            <button type="button" onclick="closeLeadContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        <div class="mb-3">
            <input id="leadContactSearchInput" type="text" placeholder="جستجوی نام یا موبایل…"
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
                <tbody id="leadContactTableBody">
                @foreach($contacts as $c)
                    <tr class="cursor-pointer hover:bg-gray-50"
                        data-name="{{ $c->full_name }}"
                        data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}"
                        onclick="selectLeadContact({{ $c->id }}, @js($c->full_name))">
                        <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div id="leadContactNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
        </div>
    </div>
</div>
