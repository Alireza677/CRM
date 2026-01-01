<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">مخاطبین مرتبط</h3>
        <div class="flex items-center gap-2">
            <button type="button"
                    class="bg-indigo-600 text-white px-4 py-2 text-sm rounded hover:bg-indigo-700 transition"
                    onclick="openOpportunityContactModal()">
                اتصال به مخاطب موجود
            </button>
            <a href="{{ route('sales.contacts.create', ['opportunity_id' => $opportunity->id]) }}"
               class="bg-blue-600 text-white px-4 py-2 text-sm rounded hover:bg-blue-700 transition">
                ایجاد مخاطب
            </a>
        </div>
    </div>

    @php
        $contacts = $contacts ?? collect();
        $allContacts = $allContacts ?? collect();
        $primaryContactId = $opportunity->contact_id ?? null;
    @endphp

    @if ($contacts->isNotEmpty())
        <div class="overflow-hidden border border-gray-200 rounded"
             data-detach-url="{{ route('sales.opportunities.detach-contact', $opportunity) }}"
             data-primary-url="{{ route('sales.opportunities.primary-contact', $opportunity) }}">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">نام</th>
                        <th class="px-4 py-2">ایمیل</th>
                        <th class="px-4 py-2">تلفن</th>
                        <th class="px-4 py-2">سمت</th>
                        <th class="px-4 py-2">سازمان</th>
                        <th class="px-4 py-2 text-left">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contacts as $contact)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <a href="{{ route('sales.contacts.show', $contact->id) }}"
                                class="text-blue-600 hover:underline">
                                    {{ $contact->full_name ?? '-' }}
                                </a>

                                @if($primaryContactId && (int) $primaryContactId === (int) $contact->id)
                                    <span class="mr-2 text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded">
                                        مخاطب اصلی
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-2">{{ $contact->email ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $contact->phone ?? $contact->mobile ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $contact->position ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $contact->organization->name ?? '-' }}</td>

                            <td class="px-4 py-2 text-left">
                                <div class="flex items-center gap-2 justify-end">
                                    @if(!$primaryContactId || (int) $primaryContactId !== (int) $contact->id)
                                        <button type="button"
                                                class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200"
                                                data-action="set-primary"
                                                data-contact-id="{{ $contact->id }}">
                                            تعیین به‌عنوان اصلی
                                        </button>
                                    @endif

                                    <button type="button"
                                            class="text-xs px-2 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200"
                                            data-action="detach-contact"
                                            data-contact-id="{{ $contact->id }}">
                                        جدا کردن
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 mt-4 text-sm">هنوز هیچ مخاطبی به این فرصت فروش متصل نشده است.</p>
    @endif
</div>

<div id="opportunityContactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     data-attach-url="{{ route('sales.opportunities.attach-contact', $opportunity) }}">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
            <button type="button" onclick="closeOpportunityContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        <div class="mb-3">
            <input id="opportunityContactSearchInput" type="text" placeholder="جستجو بر اساس نام یا شماره"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   autocomplete="off">
            <div class="mt-1 text-xs text-gray-500">برای اتصال، روی مخاطب موردنظر کلیک کنید.</div>
        </div>

        <div class="border border-gray-200 rounded overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700 sticky top-0">
                <tr>
                    <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
                    <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
                </tr>
                </thead>
                <tbody id="opportunityContactTableBody">
                @foreach($allContacts as $c)
                    <tr class="cursor-pointer hover:bg-gray-50"
                        data-name="{{ $c->full_name }}"
                        data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}"
                        onclick="handleOpportunityContactSelect({{ $c->id }}, @js($c->full_name))">
                        <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '?' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div id="opportunityContactNoResults" class="hidden p-4 text-center text-sm text-gray-500">نتیجه‌ای یافت نشد.</div>
        </div>
    </div>
</div>
