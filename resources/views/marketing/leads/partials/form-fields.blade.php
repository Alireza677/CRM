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

    
        $rawStatus = old(
            'lead_status',
            isset($lead)
                ? ($lead->lead_status ?? $lead->status)
                : null
        );

        $lead_status_value = \App\Models\SalesLead::normalizeStatus($rawStatus) ?? 'new';
    
    $originalLeadStatus = strtolower((string) (($lead->lead_status ?? $lead->status ?? '') ?: ''));

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
                    <select id="lead_status" name="lead_status" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm">
                        @foreach(FormOptionsHelper::leadStatuses() as $key => $label)
                            <option value="{{ $key }}" @selected($lead_status_value == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" id="original_lead_status" value="{{ $originalLeadStatus }}">
                    <input type="hidden" name="disqual_reason_body" id="disqual_reason_body" value="{{ old('disqual_reason_body', $lead->disqual_reason_body ?? '') }}">

                    @error('lead_status') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                    @error('disqual_reason_body') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div>
                <label for="assigned_to" class="block font-medium text-sm text-gray-700">ارجاع به</label>
                <select id="assigned_to" name="assigned_to" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">انتخاب کنید</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to', $lead->assigned_to ?? null) == $user->id)>{{ $user->name }}</option>
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
    {{-- Discard Reason Modal --}}
<div id="discardReasonModal"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">دلیل از دست رفتن سرنخ</h3>
            <button type="button" id="discardModalCloseBtn"
                    class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">علت</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach(FormOptionsHelper::leadDisqualifyReasons() as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="disqual_reasons[]" value="{{ $label }}"
                                   class="rounded border-gray-300">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="discard_reason_text" class="block text-sm font-medium text-gray-700 mb-2">
                    توضیحات <span class="text-red-600">*</span>
                </label>
                <textarea id="discard_reason_text" rows="3"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="لطفاً توضیح کوتاه بنویسید..."></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="discardModalCancelBtn"
                    class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                انصراف
            </button>
            <button type="button" id="discardModalConfirmBtn"
                    class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">
                تایید و ذخیره
            </button>
        </div>
    </div>
</div>


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    let form = document.getElementById('leadForm');
    const statusSelect = document.getElementById('lead_status');
    const originalStatusInput = document.getElementById('original_lead_status');
    const modal = document.getElementById('discardReasonModal');
    const textarea = document.getElementById('discard_reason_text');
    const confirmBtn = document.getElementById('discardModalConfirmBtn');
    const cancelBtn = document.getElementById('discardModalCancelBtn');
    const closeBtn = document.getElementById('discardModalCloseBtn');
    let submittingAfterReason = false;

    if (!form && statusSelect) form = statusSelect.closest('form');
    if (!form || !statusSelect || !modal) return;

    let disqualInput = form.querySelector('input[name="disqual_reason_body"]');
    if (!disqualInput) {
        disqualInput = document.createElement('input');
        disqualInput.type = 'hidden';
        disqualInput.name = 'disqual_reason_body';
        disqualInput.id = 'disqual_reason_body';
        form.appendChild(disqualInput);
    }

    function openModal(){
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => { if (textarea) textarea.focus(); }, 20);
    }

    function closeModal(){
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function shouldAskReason(){
        const currentStatus = (statusSelect.value || '').toLowerCase().trim();
        const originalStatus = (originalStatusInput ? originalStatusInput.value : '').toLowerCase().trim();
        const disqualAlready = disqualInput ? disqualInput.value.trim() : '';
        return currentStatus === 'discarded' && originalStatus !== 'discarded' && !disqualAlready;
    }

    form.addEventListener('submit', function(e){
        if (submittingAfterReason) return;
        if (!shouldAskReason()) return;
        e.preventDefault();
        openModal();
    });

    function collectAndSubmit(){
        const explanation = textarea ? textarea.value.trim() : '';
        const reasonChecks = modal.querySelectorAll('input[name="disqual_reasons[]"]:checked');
        const reasons = Array.from(reasonChecks).map(cb => cb.value).filter(Boolean);

        if (!explanation){
            alert('لطفا توضیح دهید که چرا این سرنخ رد شده است.');
            if (textarea) textarea.focus();
            return;
        }

        const combined = reasons.length ? reasons.join(', ') + ' - ' + explanation : explanation;
        if (disqualInput) disqualInput.value = combined;
        console.log('discard reason set', combined);

        submittingAfterReason = true;
        closeModal();
        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
    }

    if (confirmBtn) confirmBtn.addEventListener('click', collectAndSubmit);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    document.addEventListener('click', function(e){
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
});
</script>
@endpush
