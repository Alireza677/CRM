@csrf

@if(isset($contact))
    @method('PUT')
@endif

@php($contact = $contact ?? new \App\Models\Contact())
@php($selectedOrganizationId = old('organization_id', $contact->organization_id ?? ''))
<input type="hidden" name="opportunity_id" value="{{ request('opportunity_id', $contact->opportunity_id ?? '') }}">
<input type="hidden" name="lead_id" value="{{ request('lead_id', '') }}">

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label for="first_name" class="block text-sm font-medium text-gray-700">نام <span class="text-red-500">*</span></label>
        <input type="text" name="first_name" id="first_name"
               value="{{ old('first_name', $contact->first_name ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('first_name')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="last_name" class="block text-sm font-medium text-gray-700">نام خانوادگی <span class="text-red-500">*</span></label>
        <input type="text" name="last_name" id="last_name"
               value="{{ old('last_name', $contact->last_name ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('last_name')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="position" class="block text-sm font-medium text-gray-700">سمت</label>
        <input type="text" name="position" id="position"
               value="{{ old('position', $contact->position ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('position')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">ایمیل <span class="text-red-500">*</span></label>
        <input type="email" name="email" id="email"
               value="{{ old('email', $contact->email ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('email')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
<label for="website" class="block text-sm font-medium text-gray-700">وبسایت</label>
        <input type="url" name="website" id="website"
               value="{{ old('website', $contact->website ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
               placeholder="https://example.com">
        @error('website')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">تلفن ثابت</label>
        <input type="text" name="phone" id="phone"
               value="{{ old('phone', $contact->phone ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('phone')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="mobile" class="block text-sm font-medium text-gray-700">تلفن همراه</label>
        <input type="text" name="mobile" id="mobile"
               value="{{ old('mobile', $contact->mobile ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('mobile')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="organization_name" class="block text-sm font-medium text-gray-700">سازمان</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="organization_name" name="organization_name"
                           class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                           placeholder="انتخاب سازمان"
                           readonly
                           onclick="openOrganizationModal()"
                           value="{{ old('organization_name', optional($contact->organization ?? null)->name) }}">
                    <input type="hidden" id="organization_id" name="organization_id"
                           value="{{ $selectedOrganizationId }}">
                    <button type="button"
                            onclick="openCreateOrganizationModal(event)"
                            class="mt-1 inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 bg-white text-green-600 text-xl hover:bg-green-50"
                            title="ایجاد سازمان جدید">+
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">برای انتخاب سازمان روی فیلد کلیک کنید یا با + سازمان جدید بسازید.</p>
                @error('organization_id')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="company_input" class="block text-sm font-medium text-gray-700">نام شرکت (در صورت عدم انتخاب سازمان)</label>
                <input type="text" name="company" id="company_input"
                       value="{{ old('company', $contact->company ?? ($contact->organization->name ?? '')) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="در صورت تمایل نام شرکت را به‌صورت دستی وارد کنید.">
                @error('company')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="md:col-span-3">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="stateSelect" class="block text-sm font-medium text-gray-700">استان <span class="text-red-500">*</span></label>
                <select name="state" id="stateSelect"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">استان را انتخاب کنید</option>
                    @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $state => $cities)
                        <option value="{{ $state }}" {{ old('state', $contact->state ?? '') === $state ? 'selected' : '' }}>
                            {{ $state }}
                        </option>
                    @endforeach
                </select>
                @error('state')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="citySelect" class="block text-sm font-medium text-gray-700">شهر</label>
                <select name="city" id="citySelect"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        {{ old('state', $contact->state ?? '') ? '' : 'disabled' }}>
                    <option value="">{{ old('state', $contact->state ?? '') ? 'شهر را انتخاب کنید' : 'ابتدا استان را انتخاب کنید' }}</option>
                </select>
                @error('city')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700">آدرس</label>
                <textarea name="address" id="address" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $contact->address ?? '') }}</textarea>
                @error('address')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div>
        <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
        <select name="assigned_to" id="assigned_to"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">کاربر ارجاع‌گیرنده را انتخاب کنید</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                        {{ old('assigned_to', $contact->assigned_to ?? auth()->id()) == $user->id ? 'selected' : '' }}>
                    {{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}
                </option>
            @endforeach
        </select>
        @error('assigned_to')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex justify-end space-x-4 rtl:space-x-reverse mt-6">
    <a href="{{ route('sales.contacts.index') }}"
       class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-800 hover:bg-gray-300">
        انصراف
    </a>
    <button type="submit"
            class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
        ذخیره
    </button>
</div>

{{-- مودال انتخاب سازمان --}}
<div id="organizationModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب سازمان</h3>
            <button type="button" onclick="closeOrganizationModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
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

{{-- ایجاد سازمان جدید --}}
<div id="createOrganizationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-11/12 md:w-3/4 max-h-[85vh] overflow-y-auto p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">ایجاد سازمان جدید</h3>
            <button type="button" onclick="closeCreateOrganizationModal()" class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
        </div>
        <div id="createOrganizationForm">
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
                    <select name="state" id="co_stateSelect" class="mt-1 block w-full border rounded-md p-2">
                        <option value="">انتخاب استان</option>
                        @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                    <div class="text-red-500 text-xs mt-1" data-error="state"></div>
                </div>
                <div>
                    <label class="block text-sm text-gray-700">شهر</label>
                    <select name="city" id="co_citySelect" class="mt-1 block w-full border rounded-md p-2" disabled>
                        <option value="">ابتدا استان را انتخاب کنید</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1" data-error="city"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="px-4 py-2 rounded bg-gray-200" onclick="closeCreateOrganizationModal()">انصراف</button>
                <button type="button" class="px-4 py-2 rounded bg-indigo-600 text-white" onclick="submitCreateOrganization()">ذخیره</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const locations = @json(\App\Helpers\FormOptionsHelper::iranLocations());
    const stateEl = document.getElementById('stateSelect');
    const cityEl = document.getElementById('citySelect');
    const selectedCity = @json(old('city', $contact->city ?? ''));

    function fillCities(state, preset = '') {
        if (!cityEl) {
            return;
        }
        cityEl.innerHTML = '';
        if (!state || !locations[state]) {
            cityEl.disabled = true;
            cityEl.insertAdjacentHTML('beforeend', '<option value=\"\">ابتدا استان را انتخاب کنید</option>');
            return;
        }
        cityEl.disabled = false;
        cityEl.insertAdjacentHTML('beforeend', '<option value=\"\">شهر را انتخاب کنید</option>');
        locations[state].forEach(function (city) {
            const opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            if (preset && preset === city) {
                opt.selected = true;
            }
            cityEl.appendChild(opt);
        });
    }

    if (stateEl && cityEl) {
        fillCities(stateEl.value, selectedCity);
        stateEl.addEventListener('change', function () {
            fillCities(this.value);
        });
    }
});
</script>

<script>
function toggleModal(modalId, open = true, focusInputId = null) {
    const el = document.getElementById(modalId);
    if (!el) return;
    if (open) {
        el.classList.remove('hidden');
        el.classList.add('flex');
        el.setAttribute('aria-hidden', 'false');
        if (focusInputId) setTimeout(() => {
            const t = document.getElementById(focusInputId);
            if (t) t.focus();
        }, 10);
    } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
        el.setAttribute('aria-hidden', 'true');
    }
}

function openOrganizationModal(){ toggleModal('organizationModal', true, 'organizationSearchInput'); }
function closeOrganizationModal(){ toggleModal('organizationModal', false); }

function openCreateOrganizationModal(e){
    if (e) { e.stopPropagation?.(); e.preventDefault?.(); }
    toggleModal('createOrganizationModal', true);
}
function closeCreateOrganizationModal(){ toggleModal('createOrganizationModal', false); }

function selectOrganization(id, name){
    const orgId = document.getElementById('organization_id');
    const orgName = document.getElementById('organization_name');
    const companyInput = document.getElementById('company_input');
    if (orgId) orgId.value = id ?? '';
    if (orgName) orgName.value = name ?? '';
    if (companyInput) companyInput.value = name ?? '';
    closeOrganizationModal();
}

function normalizeDigits(str){
    if (!str) return '';
    return String(str)
        .replace(/[\u06F0-\u06F9]/g, (d) => String(d.charCodeAt(0) - 0x06F0))
        .replace(/[\u0660-\u0669]/g, (d) => String(d.charCodeAt(0) - 0x0660));
}
function stripSeparators(str){
    return String(str)
        .replace(/[\u200C\u200B\u00A0\s]/g,'')
        .replace(/[\,\u060C]/g,'')
        .replace(/[\.\u066B\u066C]/g,'');
}
function normalizeQuery(raw){
    const lowered = String(raw || '').toLowerCase().trim();
    const digitsFixed = normalizeDigits(lowered);
    return { text: digitsFixed, numeric: stripSeparators(digitsFixed) };
}

function makeLiveFilter({inputId, tbodyId, noResultId}) {
    const $input = document.getElementById(inputId);
    const $tbody = document.getElementById(tbodyId);
    const $noRes = document.getElementById(noResultId);
    if (!$input || !$tbody) return;

    let t = null;
    $input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(applyFilter, 150); });

    function applyFilter() {
        const { text, numeric } = normalizeQuery($input.value);
        const rows = Array.from($tbody.querySelectorAll('tr'));

        if (!text) {
            rows.forEach(tr => tr.classList.remove('hidden'));
            if ($noRes) $noRes.classList.add('hidden');
            return;
        }

        let visible = 0;
        const isPureNumber = /^[0-9]+$/.test(numeric);
        rows.forEach(tr => {
            const name  = String(tr.getAttribute('data-name')  || '').toLowerCase();
            const phone = String(tr.getAttribute('data-phone') || '');
            const byName  = name.includes(text);
            const byPhone = isPureNumber ? phone.includes(numeric) : (numeric ? phone.includes(numeric) : false);
            const match = byName || byPhone;
            if (match) { tr.classList.remove('hidden'); visible++; } else { tr.classList.add('hidden'); }
        });

        if ($noRes) (visible === 0) ? $noRes.classList.remove('hidden') : $noRes.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    makeLiveFilter({ inputId:'organizationSearchInput', tbodyId:'organizationTableBody', noResultId:'organizationNoResults' });

    const modalState = document.getElementById('co_stateSelect');
    const modalCity = document.getElementById('co_citySelect');
    const locations = @json(\App\Helpers\FormOptionsHelper::iranLocations());

    function fillModalCities(state) {
        if (!modalCity) return;
        modalCity.innerHTML = '';
        if (!state || !locations[state]) {
            modalCity.disabled = true;
            modalCity.insertAdjacentHTML('beforeend', '<option value=\"\">ابتدا استان را انتخاب کنید</option>');
            return;
        }
        modalCity.disabled = false;
        modalCity.insertAdjacentHTML('beforeend', '<option value=\"\">شهر را انتخاب کنید</option>');
        locations[state].forEach(function (city) {
            const opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            modalCity.appendChild(opt);
        });
    }

    if (modalState && modalCity) {
        modalState.addEventListener('change', function () {
            fillModalCities(this.value);
        });
    }
});

async function submitAjaxForm(formId, url, onSuccess){
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('[data-error]').forEach(el => el.textContent = '');
    const fd = form instanceof HTMLFormElement ? new FormData(form) : new FormData();
    if (!(form instanceof HTMLFormElement)) {
        form.querySelectorAll('input, select, textarea').forEach((el) => {
            if (!el.name) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!el.checked) return;
            }
            fd.append(el.name, el.value);
        });
    }

    try{
        const res  = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok){
            if (data && data.errors){
                Object.entries(data.errors).forEach(([k, msgs]) => {
                    const holder = form.querySelector(`[data-error="${k}"]`);
                    if (holder) holder.textContent = Array.isArray(msgs) ? msgs[0] : String(msgs);
                });
            } else {
                alert('Request failed. Please try again.');
            }
            return;
        }
        onSuccess && onSuccess(data);
    } catch(e){
        console.error(e);
        alert('Network error. Please try again.');
    }
}

function submitCreateOrganization(){
    submitAjaxForm('createOrganizationForm', '{{ route('sales.ajax.organizations.store') }}', function(data){
        if (data && data.id){
            const oId = document.getElementById('organization_id');
            const oName = document.getElementById('organization_name');
            const companyInput = document.getElementById('company_input');
            if (oId) oId.value = data.id;
            if (oName) oName.value = data.name;
            if (companyInput) companyInput.value = data.name;
        }
        closeCreateOrganizationModal();
    });
}

window.submitCreateOrganization = submitCreateOrganization;

document.addEventListener('click', function(e){
    ['organizationModal','createOrganizationModal'].forEach(mid => {
        const m = document.getElementById(mid);
        if (m && !m.classList.contains('hidden') && e.target === m) toggleModal(mid, false);
    });
});
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { closeOrganizationModal(); closeCreateOrganizationModal(); } });
</script>
@endpush
