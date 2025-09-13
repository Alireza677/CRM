@php 
    use App\Helpers\FormOptionsHelper;
    use App\Helpers\DateHelper;

    $fields = [
        ['prefix', 'پیشوند'],
        ['full_name', 'نام و نام خانوادگی', true],
        ['company', 'شرکت'],
        ['email', 'ایمیل'],
        ['mobile', 'شماره موبایل'],
        ['phone', 'شماره تماس'],
        ['website', 'وب‌سایت'],
        ['address', 'آدرس'],
        ['notes', 'یادداشت‌ها'],
    ];

    $lead_status_value = old('lead_status', isset($lead) ? $lead->lead_status : 'new');

    // تاریخ‌ها
    $lead_date_shamsi = old('lead_date_shamsi');
    if (!$lead_date_shamsi && !empty($lead->lead_date) && $lead->lead_date !== '0000-00-00') {
        $lead_date_shamsi = DateHelper::toJalali($lead->lead_date, 'Y/m/d');
    }
    if (!$lead_date_shamsi || strlen($lead_date_shamsi) !== 10) $lead_date_shamsi = jdate()->format('Y/m/d');

    $next_follow_up_shamsi = old('next_followup_date');
    if (!$next_follow_up_shamsi && !empty($lead->next_follow_up_date) && $lead->next_follow_up_date !== '0000-00-00') {
        $next_follow_up_shamsi = DateHelper::toJalali($lead->next_follow_up_date, 'Y/m/d');
    }
    if (!$next_follow_up_shamsi || strlen($next_follow_up_shamsi) !== 10) {
        try { $next_follow_up_shamsi = jdate($lead_date_shamsi)->addDays(7)->format('Y/m/d'); }
        catch (\Exception $e) { $next_follow_up_shamsi = jdate()->addDays(7)->format('Y/m/d'); }
    }

    $lead_date_miladi        = old('lead_date') ?? ($lead->lead_date ?? '');
    $next_follow_up_miladi   = old('next_follow_up_date') ?? ($lead->next_follow_up_date ?? '');
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
   

    {{-- فیلدهای عمومی --}}
    @foreach ($fields as $field)
        @php
            $id = $field[0];
            $label = $field[1];
            $required = $field[2] ?? false;
            $value = old($id, $lead->$id ?? '');
            if (is_array($value)) $value = '';
            $isEdit = isset($lead) && !empty($lead->id);
        @endphp

        @if(in_array($id, ['state','city'])) @continue @endif

        <div class="{{ in_array($id, ['address','notes']) ? 'md:col-span-3' : 'col-span-1' }}">
            <label for="{{ $id }}" class="block font-medium text-sm text-gray-700">
                {{ $label }} {!! $required ? '<span class="text-red-600">*</span>' : '' !!}
            </label>

            @if ($id === 'prefix')
                <select name="prefix" id="prefix"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @if($required) required @endif>
                    <option value="">انتخاب کنید</option>
                    <option value="آقای"  @selected($value=='آقای')>آقای</option>
                    <option value="خانم"  @selected($value=='خانم')>خانم</option>
                    <option value="مهندس" @selected($value=='مهندس')>مهندس</option>
                </select>

            @elseif (in_array($id, ['address','notes']))
                @php $isNotes = ($id === 'notes'); @endphp
                <textarea name="{{ $id }}" id="{{ $id }}" rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $isNotes && $isEdit ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                    @if($required) required @endif
                    @if($isNotes && $isEdit) disabled title="یادداشت اولیه قابل ویرایش نیست." @endif>{{ $value }}</textarea>
                @if($isNotes && $isEdit)
                    <p class="mt-1 text-xs text-gray-500">این یادداشت به عنوان یادداشت اولیه ذخیره شده و فقط قابل مشاهده است.</p>
                @endif

            @else
                <input type="text" name="{{ $id }}" id="{{ $id }}" value="{{ $value }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       @if($required) required @endif>
            @endif

            @error($id)
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endforeach

    {{-- استان/شهر --}}
    <div class="md:col-span-2">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium text-sm text-gray-700">استان <span class="text-red-600">*</span></label>
                <select name="state" id="stateSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">انتخاب استان</option>
                    @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
                        <option value="{{ $st }}" {{ old('state', $lead->state ?? '')===$st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
                @error('state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-medium text-sm text-gray-700">شهر</label>
                <select name="city" id="citySelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" {{ old('state', $lead->state ?? '') ? '' : 'disabled' }}>
                    <option value="">{{ old('state', $lead->state ?? '') ? 'انتخاب شهر' : 'ابتدا استان را انتخاب کنید' }}</option>
                    @php
                        $state = old('state', $lead->state ?? '');
                        $city  = old('city', $lead->city ?? '');
                        $all   = \App\Helpers\FormOptionsHelper::iranLocations();
                        $list  = $state && isset($all[$state]) ? $all[$state] : [];
                    @endphp
                    @foreach($list as $c)
                        <option value="{{ $c }}" {{ $city===$c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
                @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
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
                    opt.value = c; opt.textContent = c;
                    if (preset && preset === c) opt.selected = true;
                    cityEl.appendChild(opt);
                });
            }

            stateEl.addEventListener('change', function(){ fillCities(this.value); });
            fillCities(stateEl.value, @json(old('city', $lead->city ?? '')));
        })();
        </script>
        @endpush
    </div>
    {{-- منبع سرنخ --}}
    <div class="col-span-1">
        <label for="lead_source" class="block font-medium text-sm text-gray-700">منبع سرنخ <span class="text-red-600">*</span></label>
        <select id="lead_source" name="lead_source" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            <option value="">انتخاب کنید</option>
            @foreach(FormOptionsHelper::leadSources() as $key => $label)
                <option value="{{ $key }}" @selected(old('lead_source', $lead->lead_source ?? '') == $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('lead_source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
    {{-- وضعیت سرنخ (داخل همان grid) --}}
    <div class="col-span-1">
        <label for="lead_status" class="block font-medium text-sm text-gray-700">
            وضعیت سرنخ <span class="text-red-600">*</span>
        </label>
        <select id="lead_status" name="lead_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            @foreach(FormOptionsHelper::leadStatuses() as $key => $label)
                <option value="{{ $key }}" @selected($lead_status_value == $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('lead_status') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
    {{-- تاریخ ثبت سرنخ --}}
    <div class="col-span-1">
        <label for="lead_date_shamsi" class="block font-medium text-sm text-gray-700">تاریخ ثبت سرنخ <span class="text-red-600">*</span></label>
        <input type="text" id="lead_date_shamsi"
               class="persian-datepicker mt-1 block w-full border-gray-300 rounded-md shadow-sm"
               data-alt-field="lead_date" autocomplete="off" value="{{ $lead_date_shamsi }}">
        <input type="hidden" name="lead_date" id="lead_date" value="{{ $lead_date_miladi }}">
        @error('lead_date') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- تاریخ پیگیری بعدی --}}
    <div class="col-span-1">
        <label for="next_followup_date" class="block font-medium text-sm text-gray-700">تاریخ پیگیری بعدی</label>
        <input type="text" id="next_followup_date"
               class="persian-datepicker mt-1 block w-full border-gray-300 rounded-md shadow-sm"
               data-alt-field="next_follow_up_date" autocomplete="off" value="{{ $next_follow_up_shamsi }}">
        <input type="hidden" name="next_follow_up_date" id="next_follow_up_date" value="{{ $next_follow_up_miladi }}">
        @error('next_follow_up_date') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- ارجاع به --}}
    <div class="col-span-1">
        <label for="assigned_to" class="block font-medium text-sm text-gray-700">ارجاع به <span class="text-red-600">*</span></label>
        <select id="assigned_to" name="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            <option value="">انتخاب کنید</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('assigned_to', $lead->assigned_to ?? '') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
</div>  {{-- ⬅️ این تنها بستن grid است --}}
