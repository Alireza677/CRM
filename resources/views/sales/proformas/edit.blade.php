@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'پیش‌فاکتورها', 'url' => route('sales.proformas.index')],
        ['title' => 'ویرایش: ' . $proforma->subject]
    ];
@endphp

<div class="container mx-auto py-8 px-4" dir="rtl">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">ویرایش پیش‌فاکتور</h2>

    <form action="{{ route('sales.proformas.update', $proforma) }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- اطلاعات عمومی --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- موضوع --}}
            <div>
                <label for="subject" class="block mb-1 font-medium text-gray-700">موضوع <span class="text-red-600">*</span></label>
                <input type="text" id="subject" name="subject" value="{{ old('subject', $proforma->subject) }}" required
                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring focus:ring-blue-300">
                @error('subject') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- تاریخ پیش‌فاکتور (شمسی + مخفی میلادی) --}}
            <div>
                <label for="proforma_date_shamsi" class="block mb-1 font-medium text-gray-700">تاریخ پیش‌فاکتور</label>
                <input type="text" id="proforma_date_shamsi" class="form-control w-full border border-gray-300 rounded-md p-2" placeholder="تاریخ شمسی">
                <input type="hidden" name="proforma_date" id="proforma_date" value="{{ old('proforma_date', $proforma->proforma_date?->format('Y-m-d')) }}">
                @error('proforma_date') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- نام مخاطب --}}
            <div>
                <label for="contact_name" class="block mb-1 font-medium text-gray-700">نام مخاطب</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $proforma->contact_name) }}"
                    class="w-full border border-gray-300 rounded-md p-2">
                @error('contact_name') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- شماره پیش‌فاکتور --}}
            <div>
                <label for="proforma_number" class="block mb-1 font-medium text-gray-700">شماره پیش‌فاکتور</label>
                <input type="text" id="proforma_number" name="proforma_number" value="{{ old('proforma_number', $proforma->proforma_number) }}"
                    class="w-full border border-gray-300 rounded-md p-2">
                @error('proforma_number') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- مرحله پیش‌فاکتور --}}
            <div>
                <label for="proforma_stage" class="block mb-1 font-medium text-gray-700">
                    مرحله پیش‌فاکتور <span class="text-red-600">*</span>
                </label>
                <select id="proforma_stage" name="proforma_stage" required class="w-full border border-gray-300 rounded-md p-2">
                    <option value="">انتخاب کنید</option>
                    @foreach ($proformaStages as $value => $label)
                        <option value="{{ $value }}" {{ old('proforma_stage', $proforma->proforma_stage ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('proforma_stage')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>



            {{-- نام سازمان --}}
            <div>
                <label for="organization_name" class="block mb-1 font-medium text-gray-700">نام سازمان</label>
                <input type="text" id="organization_name" name="organization_name" value="{{ old('organization_name', $proforma->organization_name) }}"
                    class="w-full border border-gray-300 rounded-md p-2">
                @error('organization_name') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- فرصت فروش --}}
            <div>
                <label for="sales_opportunity" class="block mb-1 font-medium text-gray-700">نام فرصت فروش</label>
                <input type="text" id="sales_opportunity" name="sales_opportunity" value="{{ old('sales_opportunity', $proforma->sales_opportunity) }}"
                    class="w-full border border-gray-300 rounded-md p-2">
                @error('sales_opportunity') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ارجاع به --}}
            <div>
                <label for="assigned_to" class="block mb-1 font-medium text-gray-700">ارجاع به <span class="text-red-600">*</span></label>
                <select id="assigned_to" name="assigned_to" required
                    class="w-full border border-gray-300 rounded-md p-2">
                    <option value="">انتخاب کنید</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('assigned_to', $proforma->assigned_to) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                @error('assigned_to') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- نوع آدرس --}}
            <div>
                <label class="block mb-1 font-medium text-gray-700">نوع آدرس</label>
                <div class="space-y-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="address_type" value="invoice" class="text-blue-600" {{ old('address_type', $proforma->address_type) == 'invoice' ? 'checked' : '' }}>
                        <span class="ml-2">آدرس تحویل صورت‌حساب</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="address_type" value="product" class="text-blue-600" {{ old('address_type', $proforma->address_type) == 'product' ? 'checked' : '' }}>
                        <span class="ml-2">آدرس تحویل محصول</span>
                    </label>
                </div>
            </div>

            {{-- شهر --}}
            <div>
                <label for="city" class="block mb-1 font-medium text-gray-700">شهر</label>
                <input type="text" id="city" name="city" value="{{ old('city', $proforma->city) }}"
                    class="w-full border border-gray-300 rounded-md p-2">
                @error('city') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- استان --}}
            <div>
                <label for="state" class="block mb-1 font-medium text-gray-700">استان</label>
                <input type="text" id="state" name="state" value="{{ old('state', $proforma->state) }}"
                    class="w-full border border-gray-300 rounded-md p-2">
                @error('state') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- آدرس مشتری (تمام عرض) --}}
            <div class="md:col-span-2">
                <label for="customer_address" class="block mb-1 font-medium text-gray-700">آدرس مشتری</label>
                <textarea id="customer_address" name="customer_address" rows="3"
                    class="w-full border border-gray-300 rounded-md p-2">{{ old('customer_address', $proforma->customer_address) }}</textarea>
                @error('customer_address') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- دکمه‌ها --}}
        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('sales.proformas.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">انصراف</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ذخیره تغییرات</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/persian-datepicker/js/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-date.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/js/persian-datepicker.min.js') }}"></script>

<script>
    $(function () {
        $('#proforma_date_shamsi').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: false,
            onSelect: function (unix) {
                if (!unix) return;
                try {
                    const gDate = new persianDate(unix).toLocale('en').format('YYYY-MM-DD');
                    $('#proforma_date').val(gDate);
                } catch (e) {
                    console.error('تاریخ نامعتبر انتخاب شده', e);
                    $('#proforma_date').val('');
                }
            }
        });

        // در صورت وجود مقدار قبلی، مقدار نمایشی را تنظیم کن
        @if(old('proforma_date', $proforma->proforma_date))
            const previousDate = "{{ old('proforma_date', $proforma->proforma_date?->format('Y-MM-DD') ?? '' ) }}";
            if (previousDate) {
                const shamsi = new persianDate(previousDate).toLocale('fa').format('YYYY/MM/DD');
                $('#proforma_date_shamsi').val(shamsi);
            }
        @endif

    });
</script>
@endpush
