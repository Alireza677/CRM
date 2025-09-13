@csrf

@if(isset($contact))
    @method('PUT')
@endif


<input type="hidden" name="opportunity_id" value="{{ request('opportunity_id', $contact->opportunity_id ?? '') }}">

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="first_name" class="block text-sm font-medium text-gray-700">نام <span class="text-red-500">*</span></label>
        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $contact->first_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="last_name" class="block text-sm font-medium text-gray-700">نام خانوادگی <span class="text-red-500">*</span></label>
        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $contact->last_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">ایمیل <span class="text-red-500">*</span></label>
        <input type="email" name="email" id="email" value="{{ old('email', $contact->email ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">شماره تلفن</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $contact->phone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="mobile" class="block text-sm font-medium text-gray-700">شماره موبایل</label>
        <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $contact->mobile ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="relative">
        <label for="company_input" class="block text-sm font-medium text-gray-700">سازمان</label>
        <div class="flex">
            <input type="text" name="company" id="company_input"
                value="{{ old('company', $contact->organization->name ?? '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="نام سازمان را وارد کنید یا از جستجو انتخاب کنید">
            
            <!-- آیکن ذره‌بین -->
            <button type="button" id="open-org-modal" class="ml-2 mt-1 inline-flex items-center px-2 bg-gray-200 hover:bg-gray-300 rounded">
                🔍
            </button>
        </div>
    </div>


    <div>
    <label for="stateSelect" class="block font-medium text-sm text-gray-700">استان <span class="text-red-600">*</span></label>
    <select name="state" id="stateSelect" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">انتخاب استان</option>
        @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
            <option value="{{ $st }}" 
                {{ old('state', $contact->state ?? '') === $st ? 'selected' : '' }}>
                {{ $st }}
            </option>
        @endforeach
    </select>
    @error('state') 
        <div class="text-red-500 text-xs mt-2">{{ $message }}</div> 
    @enderror
</div>

<div>
    <label for="citySelect" class="block text-sm font-medium text-gray-700">شهر</label>
    <select name="city" id="citySelect" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        {{ old('state', $contact->state ?? '') ? '' : 'disabled' }}>
        <option value="{{ old('state', $contact->state ?? '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}">
            {{ old('state', $contact->state ?? '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}
        </option>
        @php
            $state = old('state', $contact->state ?? '');
            $city  = old('city', $contact->city ?? '');
            $all   = \App\Helpers\FormOptionsHelper::iranLocations();
            $list  = $state && isset($all[$state]) ? $all[$state] : [];
        @endphp
        @foreach($list as $c)
            <option value="{{ $c }}" {{ $city === $c ? 'selected' : '' }}>{{ $c }}</option>
        @endforeach
    </select>
    @error('city') 
        <div class="text-red-500 text-xs mt-2">{{ $message }}</div> 
    @enderror
</div>

@push('scripts')
<script>
(function(){
    const locations = @json(\App\Helpers\FormOptionsHelper::iranLocations());
    const stateEl = document.getElementById('stateSelect');
    const cityEl  = document.getElementById('citySelect');

    function fillCities(st, preset = '') {
        cityEl.innerHTML = '';
        if (!st || !locations[st]) {
            cityEl.disabled = true;
            cityEl.insertAdjacentHTML('beforeend','<option value="">ابتدا استان را انتخاب کنید</option>');
            return;
        }
        cityEl.disabled = false;
        cityEl.insertAdjacentHTML('beforeend','<option value="">انتخاب شهر</option>');
        locations[st].forEach(function(c){
            const opt = document.createElement('option');
            opt.value = c; 
            opt.textContent = c;
            if (preset && preset === c) opt.selected = true;
            cityEl.appendChild(opt);
        });
    }

    stateEl.addEventListener('change', function(){
        fillCities(this.value);
    });

    // اگر حالت ویرایش بود
    fillCities(stateEl.value, @json(old('city', $contact->city ?? '')));
})();
</script>
@endpush

    <div>
    <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
    <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value=""> انتخاب کاربر </option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('assigned_to', $contact->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}
            </option>
        @endforeach
    </select>
</div>
</div>



<div class="flex justify-end space-x-4 rtl:space-x-reverse mt-6">
    <a href="{{ route('sales.contacts.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">لغو</a>
    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">ذخیره</button>
</div>

<!-- Modal -->
<div id="org-modal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-96 max-h-[80vh] overflow-y-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">انتخاب سازمان</h2>
            <button id="close-org-modal" type="button" class="text-red-500 text-xl">×</button>
        </div>

        <!-- فیلد جستجو -->
        <input type="text" id="org-search" placeholder="جستجوی سازمان..."
               class="w-full mb-3 px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500">

        <ul id="org-list" class="space-y-2">
            @foreach($organizations as $org)
                <li>
                    <button type="button"
                            class="org-select-item w-full text-right px-3 py-2 hover:bg-gray-100 rounded text-gray-800"
                            data-name="{{ $org->name }}">
                        {{ $org->name }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<script>
document.getElementById('org-search').addEventListener('keyup', function () {
    let search = this.value.toLowerCase();
    document.querySelectorAll('#org-list li').forEach(function (item) {
        let text = item.innerText.toLowerCase();
        item.style.display = text.includes(search) ? '' : 'none';
    });
});
</script>


<script>
(function(){
    const locations = @json(\App\Helpers\FormOptionsHelper::iranLocations());
    const stateEl = document.getElementById('stateSelect');
    const cityEl  = document.getElementById('citySelect');

    function fillCities(st, preset = '') {
        cityEl.innerHTML = '';
        if (!st || !locations[st]) {
            cityEl.disabled = true;
            cityEl.insertAdjacentHTML('beforeend','<option value="">ابتدا استان را انتخاب کنید</option>');
            return;
        }
        cityEl.disabled = false;
        cityEl.insertAdjacentHTML('beforeend','<option value="">انتخاب شهر</option>');
        locations[st].forEach(function(c){
            const opt = document.createElement('option');
            opt.value = c; 
            opt.textContent = c;
            if (preset && preset === c) opt.selected = true;
            cityEl.appendChild(opt);
        });
    }

    stateEl.addEventListener('change', function(){
        fillCities(this.value);
    });

    // اگر حالت ویرایش بود
    fillCities(stateEl.value, @json(old('city', $contact->city ?? '')));
})();
</script>





