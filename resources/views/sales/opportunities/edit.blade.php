@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ویرایش: ' . $opportunity->subject]
    ];
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">ویرایش فرصت فروش</h2>

                <form method="POST" action="{{ route('sales.opportunities.update', $opportunity) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- عنوان --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">عنوان</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $opportunity->name) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm" />
                            @error('name') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
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
                            @error('organization_id')
                                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- مخاطب --}}
                        <div>
                            <label for="contact_id" class="block font-medium text-sm text-gray-700">مخاطب</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="contact_name" name="contact_name"
                                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                                    placeholder="انتخاب مخاطب" readonly>
                                <input type="hidden" id="contact_id" name="contact_id">
                                <button type="button" onclick="openContactModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
                            </div>
                            @error('contact_id')
                                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- نوع --}}
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">نوع</label>
                            <select name="type" id="type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                <option value="کسب و کار موجود" {{ old('type', $opportunity->type) == 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
                                <option value="کسب و کار جدید" {{ old('type', $opportunity->type) == 'کسب و کار جدید' ? 'selected' : '' }}>کسب و کار جدید</option>
                            </select>
                            @error('type') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- مرحله فروش --}}
                        <div>
                            <label for="stage" class="block text-sm font-medium text-gray-700">مرحله فروش</label>
                            <select name="stage" id="stage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید...</option>
                                @foreach(['در حال پیگیری', 'پیگیری در آینده', 'برنده', 'بازنده', 'سرکاری', 'ارسال پیش فاکتور'] as $stage)
                                    <option value="{{ $stage }}" {{ old('stage', $opportunity->stage) == $stage ? 'selected' : '' }}>{{ $stage }}</option>
                                @endforeach
                            </select>
                            @error('stage') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- منبع --}}
                        <div>
                            <label for="source" class="block text-sm font-medium text-gray-700">منبع</label>
                            <select name="source" id="source" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach(['وب سایت', 'مشتریان قدیمی', 'نمایشگاه', 'بازاریابی حضوری'] as $source)
                                    <option value="{{ $source }}" {{ old('source', $opportunity->source) == $source ? 'selected' : '' }}>{{ $source }}</option>
                                @endforeach
                            </select>
                            @error('source') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- ارجاع به --}}
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
                            <select name="assigned_to" id="assigned_to" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- درصد موفقیت --}}
                        <div>
                            <label for="success_rate" class="block text-sm font-medium text-gray-700">درصد موفقیت</label>
                            <input type="number" name="success_rate" id="success_rate" min="0" max="100" value="{{ old('success_rate', $opportunity->success_rate) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm" />
                            @error('success_rate') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- مبلغ --}}
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">مبلغ</label>
                            <input type="number" name="amount" id="amount" min="0" value="{{ old('amount', $opportunity->amount) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm" />
                            @error('amount') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- تاریخ پیگیری بعدی --}}
                        <div>
                            <label for="next_follow_up_shamsi" class="block text-sm font-medium text-gray-700">تاریخ پیگیری بعدی</label>
                            <input type="text" id="next_follow_up_shamsi" value="{{ $nextFollowUpDate }}" class="form-input bg-white mt-1 block w-full border border-gray-300 rounded-md shadow-sm" autocomplete="off">
                            <input type="hidden" id="next_follow_up" name="next_follow_up" value="{{ old('next_follow_up', $opportunity->next_follow_up) }}">
                            @error('next_follow_up') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- توضیحات --}}
                        <div class="col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700">توضیحات</label>
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">{{ old('description', $opportunity->description) }}</textarea>
                            @error('description') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">ذخیره</button>
                        <a href="{{ route('sales.opportunities.show', $opportunity) }}" class="text-gray-600 hover:text-gray-900">انصراف</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- مودال انتخاب مخاطب --}}
    <div id="contactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     style="display: none;">
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
    <div id="organizationModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     style="display: none;">
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


    
<script>
    $(document).ready(function () {
        $("#next_follow_up_shamsi").persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: {{ isset($nextFollowUpDate) && $nextFollowUpDate ? 'true' : 'false' }},
            onSelect: function (unix) {
                const pd = new persianDate(unix).toGregorian();
                const gDate = pd.year + '-' +
                              String(pd.month).padStart(2, '0') + '-' +
                              String(pd.day).padStart(2, '0');
                $("#next_follow_up").val(gDate);
            }
        });
    });
</script>
<script>
        function openContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>

    <script>
        function openOrganizationModal() {
            const modal = document.getElementById('organizationModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeOrganizationModal() {
            const modal = document.getElementById('organizationModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>