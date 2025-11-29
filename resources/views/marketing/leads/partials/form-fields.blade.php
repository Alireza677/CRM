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

    $contacts = $contacts ?? collect();

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

    $lead_date_miladi = old('lead_date');
    if (is_null($lead_date_miladi)) {
        $lead_date_miladi = $lead->lead_date
            ? \Illuminate\Support\Carbon::parse($lead->lead_date)->format('Y-m-d')
            : '';
    }

    $next_follow_up_miladi = old('next_follow_up_date');
    if (is_null($next_follow_up_miladi)) {
        $next_follow_up_miladi = $lead->next_follow_up_date
            ? \Illuminate\Support\Carbon::parse($lead->next_follow_up_date)->format('Y-m-d')
            : '';
    }
@endphp

<div class="space-y-8" dir="rtl">

    {{-- بخش اطلاعات سرنخ --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"> اطلاعات سرنخ</h2>
                <p class="text-sm text-gray-500 mt-1">جزئیات اولیه و تماس برای ایجاد سرنخ جدید</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @if($required) required @endif>
                            <option value="">انتخاب کنید</option>
                            <option value="آقای"  @selected($value=='آقای')>آقای</option>
                            <option value="خانم"  @selected($value=='خانم')>خانم</option>
                            <option value="مهندس" @selected($value=='مهندس')>مهندس</option>
                        </select>

                    @elseif (in_array($id, ['address','notes']))
                        @php $isNotes = ($id === 'notes'); @endphp
                        <textarea name="{{ $id }}" id="{{ $id }}" rows="2"
                            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $isNotes && $isEdit ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                            @if($required) required @endif
                            @if($isNotes && $isEdit) disabled title="یادداشت اولیه قابل ویرایش نیست." @endif>{{ $value }}</textarea>
                        @if($isNotes && $isEdit)
                            <p class="mt-1 text-xs text-gray-500">   تغییر یادداشت در زمان ویرایش سرنخ امکانپذیر نمی باشد.</p>
                        @endif

                    @else
                        <input type="text" name="{{ $id }}" id="{{ $id }}" value="{{ $value }}"
                               class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               @if($required) required @endif>
                    @endif

                    @error($id)
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            @php $isLeadEditMode = isset($lead) && !empty($lead->id); @endphp
            @unless($isLeadEditMode)
                @php
                    $selectedContactId = old('contact_id', $lead->contact_id ?? null);
                    $selectedContactName = null;
                    if (!empty($selectedContactId)) {
                        $selectedContactName = optional($contacts->firstWhere('id', (int) $selectedContactId))->full_name;
                    }
                @endphp
                <div class="md:col-span-2 col-span-1">
                    <label for="lead_contact_display" class="block font-medium text-sm text-gray-700">مخاطب مرتبط</label>
                    <div class="mt-2 flex flex-col gap-2 md:flex-row md:items-center">
                        <input type="text" id="lead_contact_display"
                               class="flex-1 rounded-md border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:border-blue-500 focus:ring-blue-500"
                               placeholder="یک مخاطب را انتخاب کنید..."
                               readonly onclick="openLeadContactModal()"
                               value="{{ $selectedContactName }}">
                        <input type="hidden" name="contact_id" id="lead_contact_id" value="{{ $selectedContactId }}">
                        <button type="button"
                                class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                onclick="openLeadContactModal()">
                            انتخاب
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">از بین مخاطبین موجود یک گزینه را برگزینید.</p>
                    @error('contact_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endunless
        </div>
    </div>
    </details>

    {{-- بخش موقعیت و وضعیت --}}
    <div class="bg-gray-50 border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"> موقعیت و وضعیت</h2>
                <p class="text-sm text-gray-500 mt-1">اطلاعات جغرافیایی، وضعیت و ارجاع سرنخ</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div>
                    <label class="block font-medium text-sm text-gray-700">استان <span class="text-red-600">*</span></label>
                    <select name="state" id="stateSelect" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">انتخاب استان</option>
                        @foreach(\App\Helpers\FormOptionsHelper::iranLocations() as $st => $cities)
                            <option value="{{ $st }}" {{ old('state', $lead->state ?? '')===$st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block font-medium text-sm text-gray-700">شهر</label>
                    <select name="city" id="citySelect" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm"></select>
                    @error('city') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="lead_source" class="block font-medium text-sm text-gray-700">منبع سرنخ <span class="text-red-600">*</span></label>
                    <select id="lead_source" name="lead_source" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <option value="">انتخاب کنید</option>
                        @foreach(FormOptionsHelper::leadSources() as $key => $label)
                            <option value="{{ $key }}" @selected(old('lead_source', $lead->lead_source ?? '') == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('lead_source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="lead_status" class="block font-medium text-sm text-gray-700">وضعیت سرنخ <span class="text-red-600">*</span></label>
                    <select id="lead_status" name="lead_status" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @foreach(FormOptionsHelper::leadStatuses() as $key => $label)
                            <option value="{{ $key }}" @selected($lead_status_value == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('lead_status') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div>
                <label for="assigned_to" class="block font-medium text-sm text-gray-700">ارجاع به <span class="text-red-600">*</span></label>
                <select id="assigned_to" name="assigned_to" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm" required>
                    <option value="">انتخاب کنید</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to', $lead->assigned_to ?? auth()->id()) == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="lead_date_shamsi" class="block font-medium text-sm text-gray-700">تاریخ ثبت سرنخ <span class="text-red-600">*</span></label>
                <input type="text" id="lead_date_shamsi"
                       class="persian-datepicker mt-2 block w-full border-gray-300 rounded-md shadow-sm"
                       data-alt-field="lead_date" autocomplete="off" value="{{ $lead_date_shamsi }}">
                <input type="hidden" name="lead_date" id="lead_date" value="{{ $lead_date_miladi }}">
                @error('lead_date') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="next_followup_date" class="block font-medium text-sm text-gray-700">تاریخ پیگیری بعدی</label>
                <input type="text" id="next_followup_date"
                       class="persian-datepicker mt-2 block w-full border-gray-300 rounded-md shadow-sm"
                       data-alt-field="next_follow_up_date" autocomplete="off" value="{{ $next_follow_up_shamsi }}">
                <input type="hidden" name="next_follow_up_date" id="next_follow_up_date" value="{{ $next_follow_up_miladi }}">
                @error('next_follow_up_date') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>
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

    @push('scripts')
    <script>
    (function(){
        const modal = document.getElementById('leadContactModal');
        if (!modal) return;

        const searchInputId = 'leadContactSearchInput';
        const tbodyId = 'leadContactTableBody';
        const noResId = 'leadContactNoResults';

        function toggleModal(open){
            if (open){
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                setTimeout(() => {
                    const s = document.getElementById(searchInputId);
                    if (s) s.focus();
                }, 10);
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
            }
        }

        window.openLeadContactModal = function(){ toggleModal(true); };
        window.closeLeadContactModal = function(){ toggleModal(false); };

        window.selectLeadContact = function(id, name){
            const idInput = document.getElementById('lead_contact_id');
            const nameInput = document.getElementById('lead_contact_display');
            if (idInput) idInput.value = id ?? '';
            if (nameInput) nameInput.value = name ?? '';
            toggleModal(false);
        };

        function normalizeDigits(str){
            if (!str) return '';
            return String(str)
                .replace(/[\\u06F0-\\u06F9]/g, d => String(d.charCodeAt(0) - 0x06F0))
                .replace(/[\\u0660-\\u0669]/g, d => String(d.charCodeAt(0) - 0x0660));
        }
        function stripSeparators(str){
            return String(str)
                .replace(/[\\u200c\\u200b\\u00a0\\s]/g,'')
                .replace(/[\\,\\u060c]/gi,'')
                .replace(/[\\.\\u066b\\u066c]/g,'');
        }
        function normalizeQuery(raw){
            const lowered = String(raw || '').toLowerCase().trim();
            const digitsFixed = normalizeDigits(lowered);
            return { text: digitsFixed, numeric: stripSeparators(digitsFixed) };
        }

        function setupFilter(){
            const input = document.getElementById(searchInputId);
            const tbody = document.getElementById(tbodyId);
            const noRes = document.getElementById(noResId);
            if (!input || !tbody) return;

            let timer = null;
            input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(applyFilter, 150); });

            function applyFilter(){
                const { text, numeric } = normalizeQuery(input.value);
                const rows = Array.from(tbody.querySelectorAll('tr'));

                if (!text){
                    rows.forEach(tr => tr.classList.remove('hidden'));
                    if (noRes) noRes.classList.add('hidden');
                    return;
                }

                let visible = 0;
                const isPureNumber = /^[0-9]+$/.test(numeric);
                rows.forEach(tr => {
                    const name  = String(tr.getAttribute('data-name')  || '').toLowerCase();
                    const phone = String(tr.getAttribute('data-phone') || '');
                    const match = name.includes(text) || (isPureNumber ? phone.includes(numeric) : (numeric ? phone.includes(numeric) : false));
                    if (match) { tr.classList.remove('hidden'); visible++; } else { tr.classList.add('hidden'); }
                });

                if (noRes) (visible === 0) ? noRes.classList.remove('hidden') : noRes.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', setupFilter);
        document.addEventListener('click', function(e){
            if (e.target === modal && !modal.classList.contains('hidden')) closeLeadContactModal();
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closeLeadContactModal();
        });
    })();
    </script>
    @endpush

    {{-- بخش اطلاعات ساختمان --}}
    @php
        $buildingSectionFields = [
            'building_usage',
            'internal_temperature',
            'external_temperature',
            'building_length',
            'building_width',
            'eave_height',
            'ridge_height',
            'wall_material',
            'insulation_status',
            'spot_heating_systems',
            'central_200_systems',
            'central_300_systems',
        ];

        $shouldOpenBuildingSection = false;
        foreach ($buildingSectionFields as $fieldName) {
            $fieldValue = old($fieldName, $lead->$fieldName ?? null);
            if (!empty($fieldValue) || $errors->has($fieldName)) {
                $shouldOpenBuildingSection = true;
                break;
            }
        }
    @endphp
    <details class="bg-gray-100 border border-gray-200 rounded-2xl shadow-sm" @if($shouldOpenBuildingSection) open @endif>
        <summary class="flex items-start justify-between p-6 cursor-pointer select-none">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"> اطلاعات ساختمان</h2>
                <p class="text-sm text-gray-500 mt-1">برای نمایش فیلد ها کلیک کنید</p>
            </div>
            <span class="text-xs text-gray-500">اطلاعات تکمیلی برای پیشنهاد دقیق‌تر</span>
        </summary>

        <div class="border-t border-gray-200 p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="building_usage" class="block font-medium text-sm text-gray-700">کاربری ساختمان</label>
                    <input type="text" name="building_usage" id="building_usage"
                           value="{{ old('building_usage', $lead->building_usage ?? '') }}"
                           class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="مثلاً صنعتی، اداری، ورزشی">
                @error('building_usage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="internal_temperature" class="block font-medium text-sm text-gray-700">دمای موردنیاز داخل</label>
                <input type="text" name="internal_temperature" id="internal_temperature"
                       value="{{ old('internal_temperature', $lead->internal_temperature ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="به سانتی‌گراد">
                @error('internal_temperature') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="external_temperature" class="block font-medium text-sm text-gray-700">دمای خارج ساختمان</label>
                <input type="text" name="external_temperature" id="external_temperature"
                       value="{{ old('external_temperature', $lead->external_temperature ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="به سانتی‌گراد">
                @error('external_temperature') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="building_length" class="block font-medium text-sm text-gray-700">طول ساختمان</label>
                <input type="text" name="building_length" id="building_length"
                       value="{{ old('building_length', $lead->building_length ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="به متر">
                @error('building_length') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="building_width" class="block font-medium text-sm text-gray-700">عرض ساختمان</label>
                <input type="text" name="building_width" id="building_width"
                       value="{{ old('building_width', $lead->building_width ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="به متر">
                @error('building_width') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="eave_height" class="block font-medium text-sm text-gray-700">ارتفاع کناره</label>
                <input type="text" name="eave_height" id="eave_height"
                       value="{{ old('eave_height', $lead->eave_height ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="به متر">
                @error('eave_height') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="ridge_height" class="block font-medium text-sm text-gray-700">ارتفاع تاج سقف</label>
                <input type="text" name="ridge_height" id="ridge_height"
                       value="{{ old('ridge_height', $lead->ridge_height ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="به متر">
                @error('ridge_height') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="wall_material" class="block font-medium text-sm text-gray-700">جنس دیوار</label>
                <input type="text" name="wall_material" id="wall_material"
                       value="{{ old('wall_material', $lead->wall_material ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="مثلاً ساندویچ‌پنل، آجر">
                @error('wall_material') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="insulation_status" class="block font-medium text-sm text-gray-700">وضعیت عایق</label>
                <select name="insulation_status" id="insulation_status"
                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">انتخاب وضعیت</option>
                    <option value="good" @selected(old('insulation_status', $lead->insulation_status ?? '') === 'good')>خوب</option>
                    <option value="medium" @selected(old('insulation_status', $lead->insulation_status ?? '') === 'medium')>متوسط</option>
                    <option value="weak" @selected(old('insulation_status', $lead->insulation_status ?? '') === 'weak')>ضعیف</option>
                </select>
                @error('insulation_status') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="spot_heating_systems" class="block font-medium text-sm text-gray-700">تعداد سامانه موضعی45kw پیشنهادی</label>
                <input type="number" min="0" step="1" name="spot_heating_systems" id="spot_heating_systems"
                       value="{{ old('spot_heating_systems', $lead->spot_heating_systems ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('spot_heating_systems') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="central_200_systems" class="block font-medium text-sm text-gray-700">تعداد سامانه مرکزی ۲۰۰ پیشنهادی</label>
                <input type="number" min="0" step="1" name="central_200_systems" id="central_200_systems"
                       value="{{ old('central_200_systems', $lead->central_200_systems ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('central_200_systems') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="central_300_systems" class="block font-medium text-sm text-gray-700">تعداد سامانه مرکزی ۳۰۰ پیشنهادی</label>
                <input type="number" min="0" step="1" name="central_300_systems" id="central_300_systems"
                       value="{{ old('central_300_systems', $lead->central_300_systems ?? '') }}"
                       class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('central_300_systems') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

  
</div>
