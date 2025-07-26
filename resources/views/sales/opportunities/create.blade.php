@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ایجاد فرصت جدید']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">
            {{ __('فرصت جدید') }}
        </h2>

        <form method="POST" action="{{ route('sales.opportunities.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- عنوان --}}
                <div>
                    <label for="name" class="block font-medium text-sm text-gray-700 required">عنوان</label>
                    <input id="name" name="name" type="text"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm form-field"
                           value="{{ old('name') }}" required>
                    @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                {{-- سازمان --}}
                <div>
                    <label for="organization_id" class="block font-medium text-sm text-gray-700">سازمان</label>
                    <div class="flex items-center gap-2">
                    <input type="text" id="organization_name" name="organization_name"
                        class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                        placeholder="انتخاب سازمان" readonly>
                        <input type="hidden" id="organization_id" name="organization_id">
                        <button type="button" onclick="openOrganizationModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
                    </div>
                    @error('organization_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                {{-- مخاطب --}}
                <div>
                    <label for="contact_display" class="block font-medium text-sm text-gray-700">مخاطب</label>
                    <div class="relative">
                        <input type="text" id="contact_display" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                        placeholder="انتخاب مخاطب..." readonly
                            value="{{ old('contact_display') ?? ($defaultContact->full_name ?? '') }}">
                        <input type="hidden" name="contact_id" id="contact_id" value="{{ old('contact_id') ?? ($defaultContact->id ?? '') }}">
                        <button type="button" onclick="openContactModal()"
                                class="absolute inset-y-0 left-0 flex items-center px-3 text-gray-500 hover:text-blue-600">🔍</button>
                    </div>
                </div>


                {{-- سایر فیلدها بدون تغییر --}}
                <div>
                    <label for="type" class="block font-medium text-sm text-gray-700 ">نوع کسب‌وکار</label>
                    <select id="type" name="type" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        <option value="کسب و کار موجود" {{ old('type') == 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
                        <option value="کسب و کار جدید" {{ old('type') == 'کسب و کار جدید' ? 'selected' : '' }}>کسب و کار جدید</option>
                    </select>
                    @error('type') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="stage" class="block font-medium text-sm text-gray-700 required">مرحله فروش</label>
                    <select name="stage" id="stage"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید...</option>
                        <option value="در حال پیگیری" {{ old('stage') == 'در حال پیگیری' ? 'selected' : '' }}>در حال پیگیری</option>
                        <option value="پیگیری در آینده" {{ old('stage') == 'پیگیری در آینده' ? 'selected' : '' }}>پیگیری در آینده</option>
                        <option value="برنده" {{ old('stage') == 'برنده' ? 'selected' : '' }}>برنده</option>
                        <option value="بازنده" {{ old('stage') == 'بازنده' ? 'selected' : '' }}>بازنده</option>
                        <option value="سرکاری" {{ old('stage') == 'سرکاری' ? 'selected' : '' }}>سرکاری</option>
                        <option value="ارسال پیش فاکتور" {{ old('stage') == 'ارسال پیش فاکتور' ? 'selected' : '' }}>ارسال پیش فاکتور</option>
                    </select>
                    @error('stage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="source" class="block font-medium text-sm text-gray-700 required">منبع سرنخ</label>
                    <select id="source" name="source" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        <option value="وب سایت" {{ old('source') == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                        <option value="مشتریان قدیمی" {{ old('source') == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                        <option value="نمایشگاه" {{ old('source') == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                        <option value="بازاریابی حضوری" {{ old('source') == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                    </select>
                    @error('source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="assigned_to" class="block font-medium text-sm text-gray-700 required">ارجاع به</label>
                    <select id="assigned_to" name="assigned_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="success_rate" class="block font-medium text-sm text-gray-700 ">درصد موفقیت</label>
                    <input id="success_rate" name="success_rate" type="number" min="0" max="100"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           value="{{ old('success_rate') }}" required>
                    @error('success_rate') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="amount" class="block font-medium text-sm text-gray-700 ">مبلغ</label>
                    <input id="amount" name="amount" type="number" min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           value="{{ old('amount') }}" required>
                    @error('amount') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="next_follow_up" class="block font-medium text-sm text-gray-700">تاریخ پیگیری بعدی</label>
                    <input type="text" id="next_follow_up_shamsi" class="form-control" placeholder="انتخاب تاریخ ">
                    <input type="hidden" name="next_follow_up" id="next_follow_up" value="{{ old('next_follow_up') }}">
                    @error('next_follow_up') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block font-medium text-sm text-gray-700">توضیحات</label>
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                    @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    ذخیره
                </button>
            </div>
        </form>
    </div>
</div>

{{-- مودال انتخاب مخاطب --}}
<div id="contactModal" 
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
            <button onclick="closeContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        <table class="w-full text-sm text-right border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
                    <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $c)
                    <tr class="cursor-pointer hover:bg-gray-50"
                        onclick="selectContact({{ $c->id }}, '{{ $c->full_name }}')">
                        <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


<!-- Organization Modal -->
<div id="organizationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <h2 class="text-lg font-bold mb-4">انتخاب سازمان</h2>
        <table class="w-full text-right border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border">نام سازمان</th>
                    <th class="p-2 border">شماره تماس</th>
                    <th class="p-2 border">انتخاب</th>
                </tr>
            </thead>
            <tbody>
                @foreach($organizations as $org)
                    <tr class="border-b">
                        <td class="p-2">{{ $org->name }}</td>
                        <td class="p-2">{{ $org->phone ?? '---' }}</td>
                        <td class="p-2">
                            <button class="text-blue-600 hover:underline" 
                                    onclick="selectOrganization({{ $org->id }}, '{{ $org->name }}')">
                                انتخاب
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4 text-left">
            <button onclick="closeOrganizationModal()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">بستن</button>
        </div>
    </div>
</div>


{{-- استایل ستاره قرمز برای فیلدهای الزامی --}}
<style>
    label.required::after {
        content: ' *';
        color: red;
    }
    
    .form-field {
        @apply mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-400;
    }

    label.required::after {
        content: ' *';
        color: red;
    }
</style>


<script>
    function openContactModal() {
        document.getElementById('contactModal').classList.remove('hidden');
        document.getElementById('contactModal').classList.add('flex');
    }

    function closeContactModal() {
        document.getElementById('contactModal').classList.add('hidden');
        document.getElementById('contactModal').classList.remove('flex');
    }

    function selectContact(id, name) {
        document.getElementById('contact_display').value = name;
        document.getElementById('contact_id').value = id;
        closeContactModal();
    }
</script>

<script>
    function openOrganizationModal() {
        document.getElementById('organizationModal').classList.remove('hidden');
    }

    function closeOrganizationModal() {
        document.getElementById('organizationModal').classList.add('hidden');
    }

    function selectOrganization(id, name) {
        document.getElementById('organization_id').value = id;
        document.getElementById('organization_name').value = name;
        closeOrganizationModal();
    }
</script>


@endsection

