@csrf

@if(isset($contact))
    @method('PUT')
@endif

@php($contact = $contact ?? new \App\Models\Contact())
@php($selectedOrganizationId = old('organization_id', $contact->organization_id ?? ''))
@php($showNewOrganization = old('create_new_org', request('create_new_org', '0')) === '1')

<input type="hidden" name="opportunity_id" value="{{ request('opportunity_id', $contact->opportunity_id ?? '') }}">
<input type="hidden" name="lead_id" value="{{ request('lead_id', '') }}">
<input type="hidden" name="create_new_org" id="create_new_org_flag" value="{{ ($showNewOrganization ?? false) ? 1 : 0 }}">

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
                <label for="organization_id" class="block text-sm font-medium text-gray-700">سازمان</label>
                <div class="flex gap-2">
                    <select name="organization_id" id="organization_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">انتخاب از فهرست سازمان‌ها</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ (string)($selectedOrganizationId ?? '') === (string)$org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button"
                            id="toggle-new-org"
                            class="mt-1 inline-flex items-center justify-center rounded-md border border-indigo-200 bg-white px-3 text-lg font-bold text-indigo-600 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            aria-expanded="{{ ($showNewOrganization ?? false) ? 'true' : 'false' }}"
                            title="ایجاد سازمان جدید">
                        +
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">برای افزودن سازمان جدید روی دکمه + کلیک کنید.</p>
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

<div id="new-org-fields"
     class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 space-y-4 {{ ($showNewOrganization ?? false) ? '' : 'hidden' }}">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-indigo-700">ایجاد سازمان جدید</h3>
        <span class="text-xs text-gray-500">شهر، استان و تلفن از اطلاعات مخاطب استفاده می‌شوند.</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 lg:col-span-3">
            <label for="new_org_name" class="block text-sm font-medium text-gray-700">نام سازمان <span class="text-red-500">*</span></label>
            <input type="text" name="new_org_name" id="new_org_name"
                   value="{{ old('new_org_name') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="نام سازمان جدید را وارد کنید">
            @error('new_org_name')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="new_org_website" class="block text-sm font-medium text-gray-700">وب‌سایت</label>
            <input type="text" name="new_org_website" id="new_org_website"
                   value="{{ old('new_org_website') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="https://example.com">
            @error('new_org_website')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="md:col-span-2">
            <label for="new_org_address" class="block text-sm font-medium text-gray-700">آدرس سازمان</label>
            <textarea name="new_org_address" id="new_org_address" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="آدرس دقیق سازمان">{{ old('new_org_address') }}</textarea>
            @error('new_org_address')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
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

    const newOrgSection = document.getElementById('new-org-fields');
    const toggleBtn = document.getElementById('toggle-new-org');
    const flagInput = document.getElementById('create_new_org_flag');
    const companyInput = document.getElementById('company_input');

    function toggleNewOrg(forceState) {
        if (!newOrgSection || !flagInput) {
            return;
        }
        const shouldShow = typeof forceState === 'boolean'
            ? forceState
            : newOrgSection.classList.contains('hidden');
        if (shouldShow) {
            newOrgSection.classList.remove('hidden');
            flagInput.value = '1';
            toggleBtn?.classList.add('bg-indigo-100', 'text-indigo-700');
            if (window.jQuery) {
                const $select = window.jQuery('#organization_id');
                if ($select.length) {
                    $select.val(null).trigger('change');
                }
            } else {
                const select = document.getElementById('organization_id');
                if (select) {
                    select.value = '';
                }
            }
        } else {
            newOrgSection.classList.add('hidden');
            flagInput.value = '0';
            toggleBtn?.classList.remove('bg-indigo-100', 'text-indigo-700');
        }
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
        }
    }

    toggleBtn?.addEventListener('click', function () {
        const nextState = newOrgSection?.classList.contains('hidden');
        toggleNewOrg(nextState);
    });

    if (flagInput && flagInput.value === '1') {
        toggleNewOrg(true);
    }

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        const $orgSelect = window.jQuery('#organization_id');
        if ($orgSelect.length) {
            $orgSelect.select2({
                dir: 'rtl',
                width: '100%',
                allowClear: true,
                placeholder: 'انتخاب یا جستجوی سازمان',
                language: {
                    noResults: () => 'هیچ سازمانی یافت نشد',
                    inputTooShort: () => 'برای جستجو تایپ کنید...',
                },
            });

            $orgSelect.on('select2:select', function (event) {
                if (companyInput && flagInput.value !== '1') {
                    companyInput.value = (event.params.data.text || '').trim();
                }
            });

            $orgSelect.on('select2:clear', function () {
                if (companyInput && flagInput.value !== '1') {
                    companyInput.value = '';
                }
            });
        }
    }
});
</script>
@endpush
