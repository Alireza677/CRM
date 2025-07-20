@php
    use App\Helpers\FormOptionsHelper;
    use App\Helpers\DateHelper;

    $lead_date_shamsi = old('lead_date_shamsi', isset($lead) ? DateHelper::toJalali($lead->lead_date) : jdate()->format('Y/m/d'));
    $next_follow_up_shamsi = old('next_followup_date', isset($lead) ? DateHelper::toJalali($lead->next_follow_up_date) : '');
@endphp

{{-- فیلدهای متنی --}}
@php
    $fields = [
        ['prefix', 'پیشوند'],
        ['full_name', 'نام و نام خانوادگی', true],
        ['company', 'شرکت'],
        ['email', 'ایمیل'],
        ['mobile', 'شماره موبایل'],
        ['phone', 'شماره تماس'],
        ['website', 'وب‌سایت'],
        ['industry', 'صنعت'],
        ['nationality', 'ملیت'],
        ['address', 'آدرس'],
        ['state', 'استان'],
        ['city', 'شهر'],
        ['notes', 'یادداشت‌ها'],
    ];
@endphp

@foreach ($fields as $field)
    @php
        $id = $field[0];
        $label = $field[1];
        $required = $field[2] ?? false;
        $value = old($id, $lead->$id ?? '');
    @endphp

    <div class="{{ in_array($id, ['address', 'notes']) ? 'md:col-span-2' : '' }}">
        <label for="{{ $id }}" class="block font-medium text-sm text-gray-700">
            {{ $label }} {!! $required ? '<span class="text-red-600">*</span>' : '' !!}
        </label>

        {{-- فیلد انتخابی برای prefix --}}
        @if ($id === 'prefix')
            <select name="prefix" id="prefix" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @if($required) required @endif>
                <option value="">انتخاب کنید</option>
                <option value="آقای" {{ $value == 'آقای' ? 'selected' : '' }}>آقای</option>
                <option value="خانم" {{ $value == 'خانم' ? 'selected' : '' }}>خانم</option>
                <option value="مهندس" {{ $value == 'مهندس' ? 'selected' : '' }}>مهندس</option>
            </select>
        
        {{-- فیلد متنی چندخطی --}}
        @elseif (in_array($id, ['address', 'notes']))
            <textarea name="{{ $id }}" id="{{ $id }}" rows="2"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      @if($required) required @endif>{{ $value }}</textarea>

        {{-- سایر فیلدهای متنی --}}
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


    

{{-- منبع سرنخ --}}
<div>
    <label for="lead_source" class="block font-medium text-sm text-gray-700">منبع سرنخ <span class="text-red-600">*</span></label>
    <select id="lead_source" name="lead_source" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        <option value="">انتخاب کنید</option>
        @foreach(FormOptionsHelper::leadSources() as $key => $label)
            <option value="{{ $key }}" {{ old('lead_source', $lead->lead_source ?? '') == $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('lead_source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

{{-- وضعیت سرنخ --}}
<div>
    <label for="lead_status" class="block font-medium text-sm text-gray-700">وضعیت سرنخ <span class="text-red-600">*</span></label>
    <select id="lead_status" name="lead_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        <option value="">انتخاب کنید</option>
        @foreach(FormOptionsHelper::leadStatuses() as $key => $label)
            <option value="{{ $key }}" {{ old('lead_status', $lead->lead_status ?? '') == $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('lead_status') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

@php
    

    // تاریخ ثبت سرنخ
    $lead_date_shamsi = old('lead_date_shamsi');

    if (!$lead_date_shamsi && !empty($lead->lead_date) && $lead->lead_date !== '0000-00-00') {
        $lead_date_shamsi = DateHelper::toJalali($lead->lead_date, 'Y/m/d');
    }

    // اگر هنوز مقدار نداره، تاریخ امروز شمسی
    if (!$lead_date_shamsi || strlen($lead_date_shamsi) !== 10) {
        $lead_date_shamsi = jdate()->format('Y/m/d');
    }

    // تاریخ پیگیری بعدی
    $next_follow_up_shamsi = old('next_followup_date');

    if (!$next_follow_up_shamsi && !empty($lead->next_follow_up_date) && $lead->next_follow_up_date !== '0000-00-00') {
        $next_follow_up_shamsi = DateHelper::toJalali($lead->next_follow_up_date, 'Y/m/d');
    }

    // اگر هنوز مقدار نداره، ۷ روز بعد از تاریخ ثبت (و بررسی فرمت تاریخ ثبت)
    if (!$next_follow_up_shamsi || strlen($next_follow_up_shamsi) !== 10) {
        try {
            $next_follow_up_shamsi = jdate($lead_date_shamsi)->addDays(7)->format('Y/m/d');
        } catch (\Exception $e) {
            $next_follow_up_shamsi = jdate()->addDays(7)->format('Y/m/d'); // پشتیبان
        }
    }

    // تاریخ میلادی‌ها برای hidden field
    $lead_date_miladi = old('lead_date') ?? ($lead->lead_date ?? '');
    $next_follow_up_miladi = old('next_follow_up_date') ?? ($lead->next_follow_up_date ?? '');
@endphp


{{-- تاریخ ثبت سرنخ --}}
<div>
    <label for="lead_date_shamsi" class="block font-medium text-sm text-gray-700">
        تاریخ ثبت سرنخ <span class="text-red-600">*</span>
    </label>
    <input type="text"
           id="lead_date_shamsi"
           class="persian-datepicker mt-1 block w-full border-gray-300 rounded-md shadow-sm"
           data-alt-field="lead_date"
           autocomplete="off"
           value="{{ $lead_date_shamsi }}">
    <input type="hidden"
           name="lead_date"
           id="lead_date"
           value="{{ $lead_date_miladi }}">
    @error('lead_date')
        <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
    @enderror
</div>

{{-- تاریخ پیگیری بعدی --}}
<div>
    <label for="next_followup_date" class="block font-medium text-sm text-gray-700">
        تاریخ پیگیری بعدی
    </label>
    <input type="text"
           id="next_followup_date"
           class="persian-datepicker mt-1 block w-full border-gray-300 rounded-md shadow-sm"
           data-alt-field="next_follow_up_date"
           autocomplete="off"
           value="{{ $next_follow_up_shamsi }}">
    <input type="hidden"
           name="next_follow_up_date"
           id="next_follow_up_date"
           value="{{ $next_follow_up_miladi }}">
    @error('next_follow_up_date')
        <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
    @enderror
</div>



{{-- ارجاع به --}}
<div>
    <label for="assigned_to" class="block font-medium text-sm text-gray-700">ارجاع به <span class="text-red-600">*</span></label>
    <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        <option value="">انتخاب کنید</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('assigned_to', $lead->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>
