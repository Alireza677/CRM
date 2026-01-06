@php
    // $opportunity (nullable): برای حالت edit موجود است، برای create خالی است
    // $users, $contacts, $organizations, $defaultContact (nullable)
    // $nextFollowUpDate (shamsi): برای edit از کنترلر پاس می‌شود

    $isEdit = isset($opportunity) && $opportunity?->id;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" dir="rtl">
    {{-- عنوان --}}
    <div>
        <label for="name" class="block font-medium text-sm text-gray-700 required">عنوان</label>
        <input id="name" name="name" type="text"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm form-field"
               value="{{ old('name', $opportunity->name ?? '') }}" required>
        @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- سازمان --}}
    <div>
        <label for="organization_id" class="block font-medium text-sm text-gray-700">سازمان</label>
        <div class="flex items-center gap-2">
            <input type="text" id="organization_name" name="organization_name"
                   class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                   placeholder="انتخاب سازمان" readonly onclick="openOrganizationModal()"
                   value="{{ old('organization_name', optional($opportunity->organization ?? null)->name) }}">
            <input type="hidden" id="organization_id" name="organization_id"
                   value="{{ old('organization_id', optional($opportunity->organization ?? null)->id) }}">
                   <!-- <button type="button"
                            onclick="openCreateOrganizationModal(event)"
                            class="mt-1 inline-flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 bg-white text-green-600 text-xl hover:bg-green-50"
                            title="ایجاد سازمان جدید">+
                    </button> -->
        </div>
        @error('organization_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- مخاطب --}}
    <div>
        <label for="contact_display" class="block font-medium text-sm text-gray-700">مخاطب</label>
        <div class="relative">
            <input type="text" id="contact_display"
                   class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                   placeholder="انتخاب مخاطب..." readonly onclick="openContactModal()"
                   value="{{ old('contact_display',
                            ($defaultContact->full_name ?? '') ?: optional($opportunity->contact ?? null)->full_name) }}">
            <input type="hidden" name="contact_id" id="contact_id"
                   value="{{ old('contact_id',
                            ($defaultContact->id ?? '') ?: optional($opportunity->contact ?? null)->id) }}">
                            <!-- <button type="button"
                                    onclick="openCreateContactModal(event)"
                                    class="absolute inset-y-0 left-0 z-20 flex items-center px-3 pointer-events-auto text-green-600 hover:text-green-700 text-2xl"
                                    title="ایجاد مخاطب جدید">+
                            </button> -->
        </div>
        @error('contact_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- نوع کسب‌وکار --}}
    <div>
        <label for="type" class="block font-medium text-sm text-gray-700">نوع کسب‌وکار</label>
        <select id="type" name="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @php $type = old('type', $opportunity->type ?? ''); @endphp
            <option value="">انتخاب کنید</option>
            <option value="کسب و کار موجود" {{ $type === 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
            <option value="کسب و کار جدید"  {{ $type === 'کسب و کار جدید'  ? 'selected' : '' }}>کسب و کار جدید</option>
        </select>
        @error('type') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- کاربری ساختمان --}}
    <div>
        <label for="building_usage" class="block font-medium text-sm text-gray-700 required">
            کاربری ساختمان
        </label>
        @php $buildingUsage = old('building_usage', $opportunity->building_usage ?? ''); @endphp
        <select id="building_usage" name="building_usage" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">انتخاب کنید...</option>
            @foreach([
                'کارگاه و یا کارخانه',
                'فضای باز و رستوران',
                'تعمیرگاه و سالن صنعتی',
                'گلخانه و پرورش گیاه',
                'مرغداری و پرورش دام و طیور',
                'فروشگاه و مراکز خرید',
                'سالن و باشگاه‌های ورزشی',
                'سالن‌های نمایش',
                'مدارس و محیط‌های آموزشی',
                'سایر'
            ] as $opt)
                <option value="{{ $opt }}" {{ $buildingUsage === $opt ? 'selected' : '' }}>
                    {{ $opt }}
                </option>
            @endforeach
        </select>
        @error('building_usage')
            <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
        @enderror
    </div>

    {{-- مرحله فروش --}}
    <div>
        <label for="stage" class="block font-medium text-sm text-gray-700 required">مرحله فروش</label>
        @php
            $stage = old('stage', $opportunity->getRawOriginal('stage') ?? '');
            $stageOptions = \App\Helpers\FormOptionsHelper::opportunityStages();
            $originalStageValue = strtolower((string) ($opportunity->getRawOriginal('stage') ?? ''));
        @endphp
        <select name="stage" id="stage" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" data-original-value="{{ $originalStageValue }}">
            <option value="">انتخاب کنید...</option>
            @foreach($stageOptions as $key => $label)
                <option value="{{ $key }}" {{ $stage === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('stage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        @error('loss_reason_body') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
{{-- منبع فرصت فروش --}}
<div>
    <label for="source" class="block font-medium text-sm text-gray-700 required">منبع فرصت فروش</label>
    @php
        $sources = \App\Helpers\FormOptionsHelper::opportunitySources();

        $selectedSource = old('source');

        if (!$selectedSource && isset($opportunity)) {
            $stored = $opportunity->source ?? null;

            if ($stored !== null) {
                if (array_key_exists($stored, $sources)) {
                    $selectedSource = $stored;
                } else {
                    $foundKey = collect($sources)->search(fn ($label) => (string)$label === (string)$stored);
                    if ($foundKey !== false) $selectedSource = $foundKey;
                }
            }
        }
    @endphp

    <select id="source" name="source" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        <option value="">انتخاب کنید</option>
        @foreach($sources as $key => $label)
            <option value="{{ $key }}" {{ (string)$selectedSource === (string)$key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @error('source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

{{-- نقش‌های کمیسیون --}}
<div>
    <label for="acquirer_user_id" class="block font-medium text-sm text-gray-700">جذب‌کننده</label>
    @php $acquirerSelected = old('acquirer_user_id', $opportunity?->getRoleUserId('acquirer') ?? ''); @endphp
    <select id="acquirer_user_id" name="acquirer_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        <option value="">انتخاب کنید</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ (string)$acquirerSelected === (string)$user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('acquirer_user_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

<div>
    <label for="relationship_owner_user_id" class="block font-medium text-sm text-gray-700">مالک اصلی فرصت</label>
    @php $relationshipOwnerSelected = old('relationship_owner_user_id', $opportunity?->getRoleUserId('relationship_owner') ?? ''); @endphp
    <select id="relationship_owner_user_id" name="relationship_owner_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        <option value="">انتخاب کنید</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ (string)$relationshipOwnerSelected === (string)$user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('relationship_owner_user_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

<div>
    <label for="closer_user_id" class="block font-medium text-sm text-gray-700">نهایی‌کننده</label>
    @php $closerSelected = old('closer_user_id', $opportunity?->getRoleUserId('closer') ?? ''); @endphp
    <select id="closer_user_id" name="closer_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        <option value="">انتخاب کنید</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ (string)$closerSelected === (string)$user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('closer_user_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

<div>
    <label for="execution_owner_user_id" class="block font-medium text-sm text-gray-700">مسئول پشتیبانی </label>
    @php $executionOwnerSelected = old('execution_owner_user_id', $opportunity?->getRoleUserId('execution_owner') ?? ''); @endphp
    <select id="execution_owner_user_id" name="execution_owner_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        <option value="">انتخاب کنید</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ (string)$executionOwnerSelected === (string)$user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('execution_owner_user_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

{{-- درصد موفقیت --}}
<div>
    <label for="success_rate" class="block font-medium text-sm text-gray-700">درصد موفقیت</label>
    <input id="success_rate" name="success_rate" type="number" min="0" max="100"
           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
           value="{{ old('success_rate', $opportunity->success_rate ?? '') }}">
    @error('success_rate') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

{{-- تاریخ پیگیری بعدی --}}
<div>
    <label for="next_follow_up_shamsi" class="block font-medium text-sm text-gray-700">تاریخ پیگیری بعدی</label>

    <input
        type="text"
        id="next_follow_up_shamsi"
        name="next_follow_up_shamsi"
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm persian-datepicker"
        data-alt-field="next_follow_up"
        dir="ltr"
        placeholder="انتخاب تاریخ"
        value="{{ old('next_follow_up_shamsi', $nextFollowUpDate ?? '') }}"
    >

    <input
        type="hidden"
        name="next_follow_up"
        id="next_follow_up"
        value="{{ old('next_follow_up', optional($opportunity->next_follow_up ?? null)->format('Y-m-d')) }}"
    >

    @error('next_follow_up') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>

{{-- توضیحات --}}
<div class="md:col-span-2 lg:col-span-3">
    <label for="description" class="block font-medium text-sm text-gray-700">توضیحات</label>
    <textarea id="description" name="description" rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $opportunity->description ?? '') }}</textarea>
    @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
</div>
</div>
