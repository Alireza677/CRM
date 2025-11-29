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

{{-- ایجاد مخاطب جدید --}}
<div id="createContactModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-11/12 md:w-3/4 max-h-[85vh] overflow-y-auto p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">ایجاد مخاطب جدید</h3>
            <button type="button" onclick="closeCreateContactModal()" class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
        </div>
        <form id="createContactForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">نام</label>
                    <input type="text" name="first_name" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="first_name"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">نام خانوادگی</label>
                    <input type="text" name="last_name" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="last_name"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">ایمیل</label>
                    <input type="email" name="email" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="email"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">شماره موبایل</label>
                    <input type="text" name="mobile" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="mobile"></div>
                </div>

                <div>
                    <label class="block text-sm text-gray-700">استان</label>
                    <select name="state" class="mt-1 block w-full border rounded-md p-2">
                        <option value="">انتخاب استان</option>
                        @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                    <div class="text-red-500 text-xs mt-1" data-error="state"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">شهر</label>
                    <input type="text" name="city" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="city"></div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-700">سازمان (اختیاری)</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="cc_org_name" class="mt-1 block w-full border rounded-md p-2 bg-gray-50" placeholder="انتخاب سازمان" readonly onclick="openOrganizationModalFor('contact')" />
                        <input type="hidden" id="cc_org_id" name="organization_id" />
                    </div>
                    <div class="text-red-500 text-xs mt-1" data-error="organization_id"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="px-4 py-2 rounded bg-gray-200" onclick="closeCreateContactModal()">لغو</button>
                <button type="button" class="px-4 py-2 rounded bg-indigo-600 text-white" onclick="submitCreateContact()">ذخیره</button>
            </div>
        </form>
    </div>
</div>

{{-- ایجاد سازمان جدید --}}
<div id="createOrganizationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-11/12 md:w-3/4 max-h-[85vh] overflow-y-auto p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">ایجاد سازمان جدید</h3>
            <button type="button" onclick="closeCreateOrganizationModal()" class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
        </div>
        <form id="createOrganizationForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">نام سازمان</label>
                    <input type="text" name="name" class="mt-1 block w-full border rounded-md p-2" required />
                    <div class="text-red-500 text-xs mt-1" data-error="name"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">شماره تلفن</label>
                    <input type="text" name="phone" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="phone"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">وبسایت</label>
                    <input type="url" name="website" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="website"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">استان</label>
                    <select name="state" class="mt-1 block w-full border rounded-md p-2">
                        <option value="">انتخاب استان</option>
                        @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                    <div class="text-red-500 text-xs mt-1" data-error="state"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">شهر</label>
                    <input type="text" name="city" class="mt-1 block w-full border rounded-md p-2" />
                    <div class="text-red-500 text-xs mt-1" data-error="city"></div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-700">مخاطب مرتبط (اختیاری)</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="co_contact_name" class="mt-1 block w-full border rounded-md p-2 bg-gray-50" placeholder="انتخاب مخاطب" readonly onclick="openContactModalFor('organization')" />
                        <input type="hidden" id="co_contact_id" name="contact_id" />
                    </div>
                    <div class="text-red-500 text-xs mt-1" data-error="contact_id"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="px-4 py-2 rounded bg-gray-200" onclick="closeCreateOrganizationModal()">انصراف</button>
                <button type="button" class="px-4 py-2 rounded bg-indigo-600 text-white" onclick="submitCreateOrganization()">ذخیره</button>
            </div>
        </form>
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
