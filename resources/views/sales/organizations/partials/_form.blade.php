<form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif
    


    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block font-medium text-sm text-gray-700">{{ __('نام سازمان') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $organization->name ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="phone" class="block font-medium text-sm text-gray-700">{{ __('شماره تلفن') }}</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $organization->phone ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('phone') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="website" class="block font-medium text-sm text-gray-700">{{ __('وب‌سایت') }}</label>
            <input id="website" name="website" type="url" value="{{ old('website', $organization->website ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('website') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
    <label for="stateSelect" class="block font-medium text-sm text-gray-700">{{ __('استان') }} <span class="text-red-600">*</span></label>
    <select name="state" id="stateSelect" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">انتخاب استان</option>
        @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
            <option value="{{ $st }}" 
                {{ old('state', $organization->state ?? '') === $st ? 'selected' : '' }}>
                {{ $st }}
            </option>
        @endforeach
    </select>
    @error('state') 
        <div class="text-red-500 text-xs mt-2">{{ $message }}</div> 
    @enderror
</div>

<div>
    <label for="citySelect" class="block font-medium text-sm text-gray-700">{{ __('شهر') }}</label>
    <select name="city" id="citySelect" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        {{ old('state', $organization->state ?? '') ? '' : 'disabled' }}>
        <option value="{{ old('state', $organization->state ?? '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}">
            {{ old('state', $organization->state ?? '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}
        </option>
        @php
            $state = old('state', $organization->state ?? '');
            $city  = old('city', $organization->city ?? '');
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



        <div>
            <label for="assigned_to" class="block font-medium text-sm text-gray-700">{{ __('ارجاع به') }}</label>
            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">انتخاب کنید</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('assigned_to', $organization->assigned_to ?? auth()->id()) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- مخاطب مرتبط --}}
        <div>
        <label for="contact_display" class="block font-medium text-sm text-gray-700">مخاطب مرتبط</label>
        <div class="relative">
            <input id="contact_display" name="contact_display" type="text"
                value="{{ old('contact_display', trim(optional(optional($organization)->contact)->first_name.' '.optional(optional($organization)->contact)->last_name)) }}"
                readonly
                class="mt-1 block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100 cursor-pointer" />
            <input type="hidden" id="contact_id" name="contact_id" value="{{ old('contact_id', $organization->contact_id ?? '') }}" />
            <button type="button" onclick="openContactsModal()"
                    class="absolute inset-y-0 left-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
            🔍
            </button>
        </div>
        @error('contact_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4">
        <label for="address" class="block font-medium text-sm text-gray-700">{{ __('آدرس') }}</label>
        <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $organization->address ?? '') }}</textarea>
        @error('address') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    <div class="mt-4">
        <label for="description" class="block font-medium text-sm text-gray-700">{{ __('توضیحات') }}</label>
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $organization->description ?? '') }}</textarea>
        @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    <div class="flex items-center justify-end mt-6">
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
            {{ __('ذخیره') }}
        </button>
      <a href="{{ route('sales.organizations.index') }}" class="btn btn-secondary">انصراف</a>

    </div>
</form>

{{-- مودال انتخاب مخاطب --}}
<div id="contactsModal"
     class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 p-4"
     aria-hidden="true">
  <div class="bg-white w-full max-w-2xl p-4 rounded shadow-lg max-h-[80vh] overflow-y-auto"
       role="dialog" aria-modal="true">
    <div class="flex justify-between items-center mb-3">
      <h2 class="text-lg font-bold text-right">انتخاب مخاطب</h2>
      <button type="button" onclick="closeContactsModal()" class="text-gray-500 hover:text-red-600 text-xl">&times;</button>
    </div>

    <div class="mb-3 flex items-center gap-2">
      <input id="contactsSearch" type="text" placeholder="جستجو بر اساس نام یا موبایل…"
             class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2 text-right" autocomplete="off">
      <span id="contactsCount" class="text-xs text-gray-500 whitespace-nowrap"></span>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-100 text-gray-700 sticky top-0">
          <tr>
            <th class="p-2 border-b">نام</th>
            <th class="p-2 border-b">موبایل</th>
          </tr>
        </thead>
        <tbody id="contactsTbody">
            @foreach($contacts as $contact)
                @php
                $full = trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));
                $digits = preg_replace('/\D+/', '', (string)($contact->mobile ?? ''));
                @endphp
                <tr class="contact-row cursor-pointer hover:bg-gray-50"
                    data-id="{{ $contact->id }}"
                    data-name="{{ $full }}"
                    data-phone="{{ $digits }}">
                <td class="p-2 border-b">{{ $full }}</td>
                <td class="p-2 border-b text-gray-500">{{ $contact->mobile ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>

      </table>

      <div id="noResultsRow" class="hidden p-4 text-center text-sm text-gray-500">نتیجه‌ای یافت نشد.</div>
    </div>

    <div class="mt-3 text-left">
      <button type="button" onclick="closeContactsModal()" class="text-red-600 hover:underline">بستن</button>
    </div>
  </div>
</div>


<script>
// باز/بستن مودال
function toggleModal(modalId, open = true, focusInputId = null) {
  const el = document.getElementById(modalId);
  if (!el) return;
  if (open) {
    el.classList.remove('hidden');
    el.classList.add('flex');
    el.style.display = 'grid';                 // ← تضمین نمایش
    el.style.placeItems = 'center';  
    el.setAttribute('aria-hidden', 'false');
    if (focusInputId) setTimeout(() => document.getElementById(focusInputId)?.focus(), 10);
  } else {
    el.classList.add('hidden');
    el.classList.remove('flex');
    el.setAttribute('aria-hidden', 'true');
  }
}

function openContactsModal() {
  toggleModal('contactsModal', true, 'contactsSearch');
  document.getElementById('contactsSearch')?.dispatchEvent(new Event('input'));
}
function closeContactsModal() { toggleModal('contactsModal', false); }

// انتخاب مخاطب (ایمن)
function selectContact(id, name) {
  const idEl   = document.getElementById('contact_id');
  const textEl = document.getElementById('contact_display');

  if (idEl)   idEl.value   = id ?? '';
  if (textEl) textEl.value = name ?? '';

  // حتی اگر فیلدی نبود، مودال بسته شود تا گیر نکند
  closeContactsModal();
}

// کلیک روی هر ردیف از طریق event delegation
document.getElementById('contactsTbody')?.addEventListener('click', function(e){
  const tr = e.target.closest('tr.contact-row');
  if (!tr) return;
  const id   = tr.dataset.id ? parseInt(tr.dataset.id, 10) : null;
  const name = tr.dataset.name || '';
  selectContact(id, name);
});

// بستن با کلیک روی بک‌دراپ
document.addEventListener('click', function(e){
  const m = document.getElementById('contactsModal');
  if (!m) return;
  if (!m.classList.contains('hidden') && e.target === m) closeContactsModal();
});

// بستن با ESC
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') closeContactsModal();
});

/* ——— جستجوی لایو با نرمال‌سازی ——— */
function normalizeDigits(str) {
  if (!str) return '';
  const fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
  return String(str).split('').map(ch => {
    const iFa = fa.indexOf(ch); if (iFa > -1) return String(iFa);
    const iAr = ar.indexOf(ch); if (iAr > -1) return String(iAr);
    return ch;
  }).join('');
}
function stripSeparators(str) {
  return String(str)
    .replace(/[\u200C\u200B\u00A0\s]/g, '')
    .replace(/[,\u060C]/g, '')
    .replace(/[.\u066B\u066C]/g, '');
}
function normalizeQuery(raw) {
  const lowered = String(raw || '').toLowerCase().trim();
  const digitsFixed = normalizeDigits(lowered);
  return { text: digitsFixed, numeric: stripSeparators(digitsFixed) };
}

(function enableContactsLiveSearch(){
  const $input = document.getElementById('contactsSearch');
  const $tbody = document.getElementById('contactsTbody');
  const $noRes = document.getElementById('noResultsRow');
  const $count = document.getElementById('contactsCount');
  if (!$input || !$tbody) return;

  let t = null;
  const apply = () => {
    const { text, numeric } = normalizeQuery($input.value);
    const rows = Array.from($tbody.querySelectorAll('tr.contact-row'));

    if (!text) {
      rows.forEach(tr => tr.classList.remove('hidden'));
      $noRes?.classList.add('hidden');
      $count && ($count.textContent = rows.length ? rows.length + ' مورد' : '');
      return;
    }

    let visible = 0;
    const isNumber = /^[0-9]+$/.test(numeric);

    rows.forEach(tr => {
      const name  = String(tr.getAttribute('data-name') || '').toLowerCase();
      const phone = String(tr.getAttribute('data-phone') || '');
      const byName  = name.includes(text);
      const byPhone = isNumber ? phone.includes(numeric) : (numeric ? phone.includes(numeric) : false);
      const match = byName || byPhone;

      tr.classList.toggle('hidden', !match);
      if (match) visible++;
    });

    if ($noRes) $noRes.classList.toggle('hidden', visible !== 0);
    if ($count) $count.textContent = visible ? (visible + ' مورد') : '۰ مورد';
  };

  $input.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(apply, 150);
  });

  // شمارش اولیه
  apply();
})();
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

    // حالت ویرایش
    fillCities(stateEl.value, @json(old('city', $organization->city ?? '')));
})();
</script>
