@php
    // $opportunity (nullable) : برای edit موجود است، برای create خالی است
    // $users, $contacts, $organizations, $defaultContact (nullable)
    // $nextFollowUpDate (shamsi) برای edit از کنترلر پاس می‌شود

    $isEdit = isset($opportunity) && $opportunity?->id;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                   placeholder="انتخاب سازمان" readonly
                   value="{{ old('organization_name', optional($opportunity->organization ?? null)->name) }}">
            <input type="hidden" id="organization_id" name="organization_id"
                   value="{{ old('organization_id', optional($opportunity->organization ?? null)->id) }}">
            <button type="button" onclick="openOrganizationModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
        </div>
        @error('organization_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- مخاطب --}}
    <div>
        <label for="contact_display" class="block font-medium text-sm text-gray-700">مخاطب</label>
        <div class="relative">
            <input type="text" id="contact_display"
                   class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                   placeholder="انتخاب مخاطب..." readonly
                   value="{{ old('contact_display',
                            ($defaultContact->full_name ?? '') ?: optional($opportunity->contact ?? null)->full_name) }}">
            <input type="hidden" name="contact_id" id="contact_id"
                   value="{{ old('contact_id',
                            ($defaultContact->id ?? '') ?: optional($opportunity->contact ?? null)->id) }}">
            <button type="button" onclick="openContactModal()"
                    class="absolute inset-y-0 left-0 flex items-center px-3 text-gray-500 hover:text-blue-600">🔍</button>
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
                'سالن و باشگاه های ورزشی',
                'سالن های نمایش',
                'مدارس و محیط های آموزشی',
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
        @php $stage = old('stage', $opportunity->stage ?? ''); @endphp
        <select name="stage" id="stage" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">انتخاب کنید...</option>
            @foreach(['در حال پیگیری','پیگیری در آینده','برنده','بازنده','سرکاری','ارسال پیش فاکتور'] as $opt)
                <option value="{{ $opt }}" {{ $stage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
        @error('stage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- منبع فرصت --}}
       <div>
            <label for="source" class="block font-medium text-sm text-gray-700 required">منبع فرصت فروش</label>

            @php
                $sourceKey = old('source', isset($opportunity) ? ($opportunity->getRawOriginal('source') ?? '') : '');
                $sources  = \App\Helpers\FormOptionsHelper::opportunitySources();
            @endphp

            <select id="source" name="source" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">منبع را انتخاب کنید</option>
                @foreach($sources as $key => $label)
                    <option value="{{ $key }}" {{ (string)$sourceKey === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            @error('source')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

    {{-- ارجاع به --}}
    <div>
        <label for="assigned_to" class="block font-medium text-sm text-gray-700 required">ارجاع به</label>
        @php $assigned = old('assigned_to', $opportunity->assigned_to ?? ''); @endphp
        <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">انتخاب کنید</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (string)$assigned === (string)$user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- درصد موفقیت --}}
    <div>
        <label for="success_rate" class="block font-medium text-sm text-gray-700">درصد موفقیت</label>
        <input id="success_rate" name="success_rate" type="number" min="0" max="100"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
               value="{{ old('success_rate', $opportunity->success_rate ?? '') }}" required>
        @error('success_rate') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    
    {{-- تاریخ پیگیری بعدی (نمایش شمسی + hidden میلادی) --}}
<div class="md:col-span-2">
    <label for="next_follow_up_shamsi" class="block font-medium text-sm text-gray-700">
        تاریخ پیگیری بعدی
    </label>

    {{-- ورودی نمایشی شمسی (فقط name + data-jdp کافیست) --}}
    <input
        type="text"
        id="next_follow_up_shamsi"
        name="next_follow_up_shamsi"
        data-jdp
        dir="ltr"
        class="form-control"
        placeholder="انتخاب تاریخ"
        value="{{ old('next_follow_up_shamsi') }}"
    >

    {{-- hidden میلادی که به دیتابیس می‌رود --}}
    <input
        type="hidden"
        name="next_follow_up"
        id="next_follow_up"
        value="{{ old('next_follow_up', $opportunity->next_follow_up ?? '') }}"
    >

    @error('next_follow_up')
        <span class="text-danger text-xs">{{ $message }}</span>
    @enderror
</div>


    {{-- توضیحات --}}
    <div class="md:col-span-2">
        <label for="description" class="block font-medium text-sm text-gray-700">توضیحات</label>
        <textarea id="description" name="description" rows="3"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $opportunity->description ?? '') }}</textarea>
        @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
</div>





